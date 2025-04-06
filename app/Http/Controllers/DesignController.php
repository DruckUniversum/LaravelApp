<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Design;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DesignController extends Controller
{
    /**
     * Zeigt die Liste öffentlicher Designs.
     */
    public function index(Request $request)
    {
        $query = Design::query();
        if ($request->filled('category')) {
            $query->where('Category_ID', $request->get('category'));
        }
        if ($request->filled('tags')) {
            $query->whereHas('tags', function ($subQuery) use ($request) {
                $subQuery->whereIn('Design_Tags.Tag_ID', $request->get('tags'));
            });
        }
        $designs = $query->get();
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
     * Erstellt ein neues Design.
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

        if(strlen(Auth::user()->Street) == 0) {
            return redirect('/settings')->with(["success" => "Bitte hinterlegen Sie Ihre Adressdaten und Namen."]);
        }

        $stlFile = $request->file('stl_file');
        $coverPicture = $request->file('cover_picture');

        if (!$stlFile->isValid() || !$coverPicture->isValid()) {
            return back()->with('error', 'Design konnte aufgrund ungültiger Dateien nicht erstellt werden.');
        }

        $stlUuid = Str::uuid();
        if (!$stlFile->storeAs('stl', "$stlUuid.stl", 'private')) {
            Log::error('Systemfehler: Upload der STL-Datei fehlgeschlagen', [
                'designer_id' => Auth::id(),
                'stl_uuid' => $stlUuid,
                'original_name' => $stlFile->getClientOriginalName()
            ]);
            return back()->with('error', 'Upload ist fehlgeschlagen.');
        }

        $coverPictureUuid = Str::uuid();
        if (!$coverPicture->storeAs('cover_picture', "$coverPictureUuid.png", 'public')) {
            Log::error('Systemfehler: Upload des Cover-Bildes fehlgeschlagen', [
                'designer_id' => Auth::id(),
                'cover_uuid' => $coverPictureUuid,
                'original_name' => $coverPicture->getClientOriginalName()
            ]);
            return back()->with('error', 'Upload ist fehlgeschlagen.');
        }

        $categoryId = $this->resolveCategory($validated, $request);
        if (!$categoryId) {
            return back()->with('error', 'Neue Kategorie muss angegeben werden.');
        }

        $design = Design::create([
            "Name" => $validated['name'],
            "STL_File" => $stlUuid,
            "Price" => $validated['price'],
            "Description" => $validated['description'],
            "Cover_Picture_File" => $coverPictureUuid,
            "License" => $validated['license'],
            "Category_ID" => $categoryId,
            "Designer_ID" => Auth::id(),
        ]);
        if (!$design) {
            Log::error('Systemfehler: Design konnte nicht in der Datenbank gespeichert werden', [
                'designer_id' => Auth::id(),
                'validated_data' => $validated
            ]);
            return back()->with('error', 'Design konnte nicht erstellt werden.');
        }

        $this->attachExistingTags($design, $request);
        $this->attachNewTags($design, $request);

        return redirect("/designs/manage")->with("success", "Design wurde erfolgreich erstellt.");
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
        if ($design->Designer_ID != Auth::id()) {
            // Sicherheitsrelevante Auffälligkeit: unautorisierter Zugriff
            Log::error('Sicherheitsrelevant: Unautorisierter Versuch der Design-Aktualisierung', [
                'design_id' => $design->Design_ID,
                'designer_id' => Auth::id(),
            ]);
            return redirect("/designs/manage")->with('error', 'Keine Berechtigung.');
        }

        if ($request->hasFile("stl_file")) {
            $stlFile = $request->file('stl_file');
            if (!$stlFile->isValid()) {
                return back()->with('error', 'Ungültige STL-Datei.');
            }
            $stlUuid = Str::uuid();
            if (!$stlFile->storeAs('stl', "$stlUuid.stl", 'private')) {
                Log::error('Systemfehler: Upload der STL-Datei fehlgeschlagen', [
                    'design_id' => $design->Design_ID,
                    'stl_uuid' => $stlUuid,
                    'original_name' => $stlFile->getClientOriginalName()
                ]);
                return back()->with('error', 'Upload ist fehlgeschlagen.');
            }
            $design->update(["STL_File" => $stlUuid]);
        }

        if ($request->has('cover_picture')) {
            $coverPicture = $request->file('cover_picture');
            if (!$coverPicture->isValid()) {
                return back()->with('error', 'Ungültiges Cover-Bild.');
            }
            $coverPictureUuid = Str::uuid();
            if (!$coverPicture->storeAs('cover_picture', "$coverPictureUuid.png", 'public')) {
                Log::error('Systemfehler: Upload des Cover-Bildes fehlgeschlagen', [
                    'design_id' => $design->Design_ID,
                    'cover_uuid' => $coverPictureUuid,
                    'original_name' => $coverPicture->getClientOriginalName()
                ]);
                return back()->with('error', 'Upload ist fehlgeschlagen.');
            }
            $design->update(["Cover_Picture_File" => $coverPictureUuid]);
        }

        if ($validated["category"] === "new") {
            $categoryId = $this->resolveCategory($validated, $request);
            if (!$categoryId) {
                return back()->with('error', 'Neue Kategorie muss angegeben werden.');
            }
        } else {
            $categoryId = intval($validated["category"]);
        }

        $design->update([
            "Name" => $validated['name'],
            "Price" => $validated['price'],
            "Description" => $validated['description'],
            "License" => $validated['license'],
            "Category_ID" => $categoryId,
        ]);

        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, "tag_")) {
                $tagId = intval(substr($key, 4));
                if (intval($value) === 0 && in_array($tagId, array_column($design->tags()->get()->toArray(), "Tag_ID"))) {
                    $design->tags()->detach($tagId);
                } elseif (intval($value) === 1 && !in_array($tagId, array_column($design->tags()->get()->toArray(), "Tag_ID"))) {
                    $design->tags()->attach($tagId);
                }
            }
        }

        if ($request->filled("new_tags")) {
            $tags = explode(",", $request->get("new_tags"));
            foreach ($tags as $tag) {
                $formattedTag = str_replace(" ", "-", trim(strtolower($tag)));
                $tagObj = Tag::firstOrCreate(["Name" => $formattedTag]);
                $design->tags()->attach($tagObj->Tag_ID);
            }
        }

        return redirect("/designs/manage")->with("success", "Design wurde erfolgreich aktualisiert.");
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
        if ($design->Designer_ID != Auth::id()) {
            Log::error('Sicherheitsrelevant: Unautorisierter Versuch der Design-Löschung', [
                'design_id' => $design->Design_ID,
                'designer_id' => Auth::id()
            ]);
            return redirect("/designs/manage")->with('error', 'Keine Berechtigung.');
        }
        if (!$design->delete()) {
            Log::error('Systemfehler: Design-Löschung fehlgeschlagen', [
                'design_id' => $validated['design_id'],
                'designer_id' => Auth::id()
            ]);
            return redirect("/designs/manage")->with('error', 'Design konnte nicht gelöscht werden.');
        }
        return redirect("/designs/manage")->with('success', 'Design erfolgreich gelöscht.');
    }

    private function resolveCategory(array $validated, Request $request): ?int
    {
        if ($validated["category"] === "new") {
            if (!$request->filled('new_category')) {
                return null;
            }
            $newCategory = trim($request->input('new_category'));
            $category = Category::create(["Name" => $newCategory]);
            if (!$category) {
                Log::error('Systemfehler: Fehler beim Erstellen der neuen Kategorie', [
                    'category_name' => $newCategory,
                    'designer_id' => Auth::id()
                ]);
                return null;
            }
            return $category->Category_ID;
        }
        return intval($validated["category"]);
    }

    private function attachExistingTags(Design $design, Request $request): void
    {
        $existingTagIds = array_column($design->tags()->get()->toArray(), "Tag_ID");
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, "tag_") && intval($value) === 1) {
                $tagId = intval(substr($key, 4));
                if (!in_array($tagId, $existingTagIds)) {
                    $design->tags()->attach($tagId);
                }
            }
        }
    }

    private function attachNewTags(Design $design, Request $request): void
    {
        if ($request->filled("new_tags")) {
            $tags = explode(",", $request->get("new_tags"));
            foreach ($tags as $tag) {
                $formattedTag = str_replace(" ", "-", trim(strtolower($tag)));
                $tagObj = Tag::firstOrCreate(["Name" => $formattedTag]);
                $design->tags()->attach($tagObj->Tag_ID);
            }
        }
    }
}
