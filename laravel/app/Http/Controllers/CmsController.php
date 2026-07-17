<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CmsController extends Controller
{
    public function index(Request $request)
    {
        $query = Content::query();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $contents = $query->orderBy('sort_order')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($contents);
        }

        return view('cms.index', compact('contents'));
    }

    public function create()
    {
        return view('cms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'type' => 'required|in:hero,page,banner,faq,settings',
            'slug' => 'nullable|string|max:200|unique:contents,slug',
            'media' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'attribute' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        Content::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'slug' => $validated['slug'],
            'media' => $validated['media'] ?? null,
            'content' => $validated['content'] ?? null,
            'attribute' => $validated['attribute'] ?? null,
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('CMS Content', Content::latest()->first());

        return redirect()->route('cms.index')
            ->with('success', 'Content created successfully.');
    }

    public function edit(Content $content)
    {
        return view('cms.edit', compact('content'));
    }

    public function update(Request $request, Content $content)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'type' => 'required|in:hero,page,banner,faq,settings',
            'slug' => 'nullable|string|max:200|unique:contents,slug,' . $content->id,
            'media' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'attribute' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $content->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'slug' => $validated['slug'],
            'media' => $validated['media'] ?? null,
            'content' => $validated['content'] ?? null,
            'attribute' => $validated['attribute'] ?? null,
            'status' => $validated['status'],
            'sort_order' => $validated['sort_order'] ?? 0,
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('CMS Content', $content, $content->toArray());

        return redirect()->route('cms.index')
            ->with('success', 'Content updated successfully.');
    }

    public function destroy(Request $request, Content $content)
    {
        ActivityLogger::deleted('CMS Content', $content);
        $content->update(['deleted_by' => auth('admin')->id()]);
        $content->delete();

        return redirect()->route('cms.index')
            ->with('success', 'Content deleted successfully.');
    }
}
