<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Tool;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->paginate(15);
        return view('categories.index', compact('categories'));
    }

    public function create()
    {
        return view('categories.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:10|unique:tool_categories,code',
        ]);

        Category::create($request->only('category_name', 'description', 'code'));

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'nullable|string|max:10|unique:tool_categories,code,' . $id,
        ]);

        $category = Category::findOrFail($id);
        $category->update($request->only('category_name', 'description', 'code'));

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    /**
     * Return next tool code for a category based on its `code` prefix.
     * Example: prefix LPT, existing LPT-001, LPT-002 => returns LPT-003
     */
    public function nextCode($id)
    {
        $category = Category::findOrFail($id);
        $prefix = $category->code;

        if (!$prefix) {
            return response()->json(['next' => null]);
        }

        // Ambil semua kode yang cocok dengan prefix lalu cari suffix terbesar
        $codes = Tool::where('category_id', $category->id)
            ->where('tool_code', 'like', $prefix.'-%')
            ->pluck('tool_code')
            ->toArray();

        $max = 0;
        foreach ($codes as $c) {
            if (preg_match('/-(\d+)$/', $c, $m)) {
                $n = intval($m[1]);
                if ($n > $max) $max = $n;
            }
        }

        $num = $max + 1;

        $next = sprintf('%s-%03d', $prefix, $num);
        return response()->json(['next' => $next]);
    }

    public function destroy($id)
    {
        Category::destroy($id);
        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}