<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Design;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DesignController extends Controller
{
    /**
     * Zeigt die Liste öffentlicher Designs.
     */
    public function index(Request $request)
    {
        if($request->has("category")) {
            $designs = Design::where("Category_ID", $request->get("category"))->get();
        }
        if($request->has("tags")) {
            $tags = $request->get("tags");
            $designs = Design::whereHas('tags', function ($query) use ($tags) {
                $query->whereIn('Design_Tags.Tag_ID', $tags);
            })->get();
        }
        else if(!$request->has("category") && !$request->has("tags")) $designs = Design::all();
        $categories = Category::all();
        $tags = Tag::all();
        return view('designs', compact('designs', 'categories', 'tags'));
    }
    /**
     * Zeigt die Liste eigener Designs.
     */
    public function indexManage()
    {
        $designs = Design::where("Designer_ID", Auth::id())->get();
        $categories = Category::all();
        $tags = Tag::all();
        return view('managedesigns', compact('designs', 'categories', 'tags'));
    }

    /**
     * Zeigt die Seite zum Erstellen eines neuen Designs.
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'stl_file' => 'required|file|mimetypes:model/stl,application/octet-stream',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'required|string|max:5000',
            'cover_picture' => 'required|file|mimes:png',
            'license' => 'string|max:255',
        ]);
        if (!$request->file('stl_file')->isValid()) return back()->with('error', 'Design nicht erstellt, da STL Datei ungültig.');
        if (!$request->file('cover_picture')->isValid()) return back()->with('error', 'Design nicht erstellt, da Cover Bild ungültig.');

        // Store the STL file in the 'storage' directory on the 'private' disk and fetch uuid
        $stlUuid = Str::uuid();
        $res = $request->file('stl_file')->storeAs('stl', "$stlUuid.stl", 'private');
        if (!$res) return back()->with('error', 'Upload ist fehlgeschlagen.');

        // Store the cover picture in the 'storage' directory on the 'public' disk and fetch uuid
        $coverPictureUuid = Str::uuid();
        $res = $request->file('cover_picture')->storeAs('cover_picture', "$coverPictureUuid.png", 'public');
        if (!$res) return back()->with('error', 'Upload ist fehlgeschlagen.');

        if($validated["category"] == "new") {
            if(!$request->has('new_category') || strlen($request['new_category']) == 0) return back()->with('error', 'Neue Kategorie muss angegeben werden.');
            $category = Category::create([
                "Name" => $request['new_category'],
            ]);
            $category = $category->Category_ID;
        } else {
            $category = intval($validated["category"]);
        }

        $design = Design::create([
            "Name" => $validated['name'],
            "STL_File" => $stlUuid,
            "Price" => $validated['price'],
            "Description" => $validated['description'],
            "Cover_Picture_File" => $coverPictureUuid,
            "License" => $validated['license'],
            "Category_ID" => $category,
            "Designer_ID" => Auth::id(),
        ]);
        if(!$design) return back()->with('error', 'Design konnte nicht erstellt werden.');


        // Tags verarbeiten
        foreach($request->all() as $key => $value) {
            if(str_starts_with($key, "tag_")) { // Prüft, ob das Feld ein Tag repräsentiert (Präfix "tag_")
                $tagId = intval(substr($key, 4)); // Extrahiert die Tag-ID aus dem Feldnamen
                if(
                    intval($value) == 1 &&  // Überprüft, ob das Tag aktiviert (zugewiesen) wurde
                    !in_array($tagId, array_column($design->tags()->get()->toArray(), "Tag_ID"))
                ) {
                    $design->tags()->attach($tagId);
                    if(!$design->tags()->where('Design_Tags.Tag_ID', $tagId)->exists()) return back()->with("error", "Tag konnte nicht verknüpft werden!");
                }
            }
        }

        // Neue Tags hinzufügen
        if($request->has("new_tags") && strlen($request->new_tags) > 0) {
            $tags = explode(",", $request->get("new_tags")); // Trennt neue Tags durch Komma
            foreach($tags as $tag) {
                $tag = trim(strtolower($tag)); // Entfernt Leerzeichen und wandelt in Kleinbuchstaben um
                $tag = str_replace(" ", "-", $tag); // Ersetzt Leerzeichen in Tags durch Bindestriche
                $tagObj = Tag::where("Name", $tag)->first();
                if(!$tagObj) {
                    $tagObj = Tag::create([
                        "Name" => $tag
                    ]); // Erstellt ein neues Tag, wenn es noch nicht existiert
                    if(!$tagObj) return back()->with("error", "Tag konnte nicht erstellt werden!");
                }
                $design->tags()->attach($tagObj->Tag_ID);
                if(!$design->tags()->where('Design_Tags.Tag_ID', $tagObj->Tag_ID)->exists()) return back()->with("error", "Tag konnte nicht verknüpft werden!");
            }
        }

        return redirect("/designs/manage")->with("success", "Design wurd erfolgreich erstellt.");
    }

    /**
     * Aktualisiert ein existierendes Design.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'design_id' => 'required|exists:App\Models\Design,Design_ID',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'required|string|max:5000',
            'license' => 'string|max:255',
        ]);
        $design = Design::find($validated["design_id"]);
        if($design->Designer_ID != auth()->id()) return redirect("/designs/manage")->with('error', 'Keine Berechtigung.');

        if ($request->has("stl_file")) {
            if(!$request->file('stl_file')->isValid()) return back()->with('error', 'STL-File ist ungültig.');
            // Store the STL file in the 'storage' directory on the 'private' disk and fetch uuid
            $stlUuid = Str::uuid();
            $res = $request->file('stl_file')->storeAs('stl', "$stlUuid.stl", 'private');
            if (!$res) return back()->with('error', 'Upload ist fehlgeschlagen.');

            $res = $design->update([
                "STL_File" => $stlUuid,
            ]);
            if (!$res) return back()->with('error', 'Upload ist fehlgeschlagen.');
        }

        if ($request->has('cover_picture')) {
            if(!$request->file('stl_file')->isValid()) return back()->with('error', 'Cover Picture File ist ungültig.');
            // Store the cover picture in the 'storage' directory on the 'public' disk and fetch uuid
            $coverPictureUuid = Str::uuid();
            $res = $request->file('cover_picture')->storeAs('cover_picture', "$coverPictureUuid.png", 'public');
            if (!$res) return back()->with('error', 'Upload ist fehlgeschlagen.');

            $res = $design->update([
                "Cover_Picture_File" => $coverPictureUuid,
            ]);
            if (!$res) return back()->with('error', 'Upload ist fehlgeschlagen.');
        }

        if($validated["category"] == "new") {
            if(!$request->has('new_category') || strlen($request['new_category']) == 0) return back()->with('error', 'Neue Kategorie muss angegeben werden.');
            $category = Category::create([
                "Name" => $request['new_category'],
            ]);
            $category = $category->Category_ID;
        } else {
            $category = intval($validated["category"]);
        }

        $res = $design->update([
            "Name" => $validated['name'],
            "Price" => $validated['price'],
            "Description" => $validated['description'],
            "License" => $validated['license'],
            "Category_ID" => $category,
        ]);
        if(!$res) return back()->with('error', 'Design konnte nicht gespeichert werden.');


        // Tags verarbeiten
        foreach($request->all() as $key => $value) {
            if(str_starts_with($key, "tag_")) { // Prüft, ob das Feld ein Tag repräsentiert (Präfix "tag_")
                $tagId = intval(substr($key, 4)); // Extrahiert die Tag-ID aus dem Feldnamen
                if(
                    intval($value) == 0 &&  // Überprüft, ob das Tag aktiviert (zugewiesen) wurde
                    in_array($tagId, array_column($design->tags()->get()->toArray(), "Tag_ID"))
                ) {
                    $design->tags()->detach($tagId);
                    if($design->tags()->where('Design_Tags.Tag_ID', $tagId)->exists()) return back()->with("error", "Tag konnte nicht verknüpft werden!");
                }
                elseif(
                    intval($value) == 1 &&  // Überprüft, ob das Tag aktiviert (zugewiesen) wurde
                    !in_array($tagId, array_column($design->tags()->get()->toArray(), "Tag_ID"))
                ) {
                    $design->tags()->attach($tagId);
                    if(!$design->tags()->where('Design_Tags.Tag_ID', $tagId)->exists()) return back()->with("error", "Tag konnte nicht verknüpft werden!");
                }
            }
        }

        // Neue Tags hinzufügen
        if($request->has("new_tags") && strlen($request->new_tags) > 0) {
            $tags = explode(",", $request->get("new_tags")); // Trennt neue Tags durch Komma
            foreach($tags as $tag) {
                $tag = trim(strtolower($tag)); // Entfernt Leerzeichen und wandelt in Kleinbuchstaben um
                $tag = str_replace(" ", "-", $tag); // Ersetzt Leerzeichen in Tags durch Bindestriche
                $tagObj = Tag::where("Name", $tag)->first();
                if(!$tagObj) {
                    $tagObj = Tag::create([
                        "Name" => $tag
                    ]); // Erstellt ein neues Tag, wenn es noch nicht existiert
                    if(!$tagObj) return back()->with("error", "Tag konnte nicht erstellt werden!");
                }
                $design->tags()->attach($tagObj->Tag_ID);
                if(!$design->tags()->where('Design_Tags.Tag_ID', $tagObj->Tag_ID)->exists()) return back()->with("error", "Tag konnte nicht verknüpft werden!");
            }
        }

        return redirect("/designs/manage")->with("success", "Design wurde erfolgreich aktualisiert.");}

    /**
     * Löscht ein Design.
     */
    public function delete(Request $request)
    {
        $validated = $request->validate([
            'design_id' => 'required|exists:App\Models\Design,Design_ID',
        ]);
        $design = Design::find($validated["design_id"]);
        if($design->Designer_ID != auth()->id()) return redirect("/designs/manage")->with('error', 'Keine Berechtigung.');
        if(!$design->delete()) return redirect("/designs/manage")->with('error', 'Design konnte nicht gelöscht werden.');

        return redirect("/designs/manage")->with('success', 'Design erfolgreich gelöscht.');
    }
}
