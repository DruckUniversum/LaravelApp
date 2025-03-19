<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Design;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DesignController extends Controller
{
    /**
     * Zeigt die Liste öffentlicher Designs.
     */
    public function index()
    {
        $designs = Design::all();
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
    {   error_log(var_export($request->all(), true));
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
            if(!$request->has('new_category') || $request['new_category'] != "") return back()->with('error', 'Neue Kategorie muss angegeben werden.');
            $category = Category::create([
                "Name" => $request['new_category'],
            ]);
            $category = $category->Category_ID;
        } else {
            $category = intval($validated["category"]);
        }
        error_log($category);

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

        return redirect("/designs/manage")->with("success", "Design wurd erfolgreich erstellt.");
    }

    /**
     * Aktualisiert ein existierendes Design.
     */
    public function update(Request $request)
    {
        $design = Design::where('id', $id)->where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Optionaler Bild-Upload
        ]);

        if ($request->hasFile('image')) {
            // Altes Bild löschen (optional, falls gewünscht)
            if ($design->image_path) {
                \Storage::disk('public')->delete($design->image_path);
            }
            // Neues Bild hochladen
            $design->image_path = $request->file('image')->store('designs', 'public');
        }

        $design->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'is_public' => $validated['is_public'] ?? $design->is_public,
        ]);

        return redirect()->route('managedesigns', $design->id)->with('success', 'Design erfolgreich aktualisiert.');
    }

    /**
     * Löscht ein Design.
     */
    public function delete(Request $request)
    {
        $validated = $request->validate([
            'design_id' => 'required|exists:App\Models\Design,Design_ID',
        ]);
        $design = Design::find($validated["design_id"]);
        if(!$design->delete()) return redirect("/designs/manage")->with('error', 'Design konnte nicht gelöscht werden.');

        return redirect("/designs/manage")->with('success', 'Design erfolgreich gelöscht.');
    }
}
