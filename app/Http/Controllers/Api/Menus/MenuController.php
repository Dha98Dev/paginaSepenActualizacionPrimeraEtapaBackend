<?php

namespace App\Http\Controllers\Api\Menus;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use DB;
use Illuminate\Http\Request;

class MenuController extends Controller
{
        public function index()
    {
        $menus = Menu::withCount('menu_items')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'data' => $menus,
        ]);
    }

    public function show(int $id)
    {
        $menu = Menu::with([
            'menu_items' => function ($query) {
                $query->whereNull('parent_id')
                    ->orderBy('sort_order')
                    ->with([
                        'menu_items' => function ($q) {
                            $q->orderBy('sort_order');
                        }
                    ]);
            }
        ])->find($id);

        if (!$menu) {
            return response()->json([
                'ok' => false,
                'message' => 'Menú no encontrado.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $menu,
        ]);
    }

    public function showByCode(string $code)
    {
        $menu = Menu::where('code', $code)
            ->where('is_active', true)
            ->with([
                'menu_items' => function ($query) {
                    $query->whereNull('parent_id')
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->with([
                            'menu_items' => function ($q) {
                                $q->where('is_active', true)
                                    ->orderBy('sort_order');
                            }
                        ]);
                }
            ])
            ->first();

        if (!$menu) {
            return response()->json([
                'ok' => false,
                'message' => 'Menú no encontrado.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $menu,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:menus,code',
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $menu = Menu::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Menú creado correctamente.',
            'data' => $menu,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'ok' => false,
                'message' => 'Menú no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:menus,code,' . $menu->id,
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $menu->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'is_active' => $validated['is_active'] ?? $menu->is_active,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Menú actualizado correctamente.',
            'data' => $menu,
        ]);
    }

    public function destroy(int $id)
    {
        $menu = Menu::find($id);

        if (!$menu) {
            return response()->json([
                'ok' => false,
                'message' => 'Menú no encontrado.',
            ], 404);
        }

        DB::transaction(function () use ($menu) {
            MenuItem::where('menu_id', $menu->id)->delete();
            $menu->delete();
        });

        return response()->json([
            'ok' => true,
            'message' => 'Menú eliminado correctamente.',
        ]);
    }

    public function storeItem(Request $request, int $menuId)
    {
        $menu = Menu::find($menuId);

        if (!$menu) {
            return response()->json([
                'ok' => false,
                'message' => 'Menú no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:menu_items,id',
            'label' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
            'badge' => 'nullable|string|max:100',
            'link_type' => 'nullable|string|in:internal,external,none',
            'router_link' => 'nullable|string|max:500',
            'external_url' => 'nullable|string|max:1000',
            'target' => 'nullable|string|in:_self,_blank',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = MenuItem::where('id', $validated['parent_id'])
                ->where('menu_id', $menu->id)
                ->first();

            if (!$parent) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El elemento padre no pertenece a este menú.',
                ], 422);
            }
        }

        $sortOrder = $validated['sort_order'] ?? (
            MenuItem::where('menu_id', $menu->id)
                ->where('parent_id', $validated['parent_id'] ?? null)
                ->max('sort_order') + 1
        );

        $item = MenuItem::create([
            'menu_id' => $menu->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'label' => $validated['label'],
            'icon' => $validated['icon'] ?? null,
            'badge' => $validated['badge'] ?? null,
            'link_type' => $validated['link_type'] ?? 'none',
            'router_link' => $validated['router_link'] ?? null,
            'external_url' => $validated['external_url'] ?? null,
            'target' => $validated['target'] ?? '_self',
            'sort_order' => $sortOrder,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Elemento creado correctamente.',
            'data' => $item,
        ], 201);
    }

    public function updateItem(Request $request, int $itemId)
    {
        $item = MenuItem::find($itemId);

        if (!$item) {
            return response()->json([
                'ok' => false,
                'message' => 'Elemento no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:menu_items,id',
            'label' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
            'badge' => 'nullable|string|max:100',
            'link_type' => 'nullable|string|in:internal,external,none',
            'router_link' => 'nullable|string|max:500',
            'external_url' => 'nullable|string|max:1000',
            'target' => 'nullable|string|in:_self,_blank',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        if (!empty($validated['parent_id'])) {
            if ((int) $validated['parent_id'] === (int) $item->id) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Un elemento no puede ser padre de sí mismo.',
                ], 422);
            }

            $parent = MenuItem::where('id', $validated['parent_id'])
                ->where('menu_id', $item->menu_id)
                ->first();

            if (!$parent) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El elemento padre no pertenece a este menú.',
                ], 422);
            }
        }

        $item->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'label' => $validated['label'],
            'icon' => $validated['icon'] ?? null,
            'badge' => $validated['badge'] ?? null,
            'link_type' => $validated['link_type'] ?? 'none',
            'router_link' => $validated['router_link'] ?? null,
            'external_url' => $validated['external_url'] ?? null,
            'target' => $validated['target'] ?? '_self',
            'sort_order' => $validated['sort_order'] ?? $item->sort_order,
            'is_active' => $validated['is_active'] ?? $item->is_active,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Elemento actualizado correctamente.',
            'data' => $item,
        ]);
    }

    public function destroyItem(int $itemId)
    {
        $item = MenuItem::find($itemId);

        if (!$item) {
            return response()->json([
                'ok' => false,
                'message' => 'Elemento no encontrado.',
            ], 404);
        }

        DB::transaction(function () use ($item) {
            MenuItem::where('parent_id', $item->id)->delete();
            $item->delete();
        });

        return response()->json([
            'ok' => true,
            'message' => 'Elemento eliminado correctamente.',
        ]);
    }

    public function reorderItems(Request $request, int $menuId)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:menu_items,id',
            'items.*.parent_id' => 'nullable|integer|exists:menu_items,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        $menu = Menu::find($menuId);

        if (!$menu) {
            return response()->json([
                'ok' => false,
                'message' => 'Menú no encontrado.',
            ], 404);
        }

        DB::transaction(function () use ($validated, $menu) {
            foreach ($validated['items'] as $itemData) {
                MenuItem::where('id', $itemData['id'])
                    ->where('menu_id', $menu->id)
                    ->update([
                        'parent_id' => $itemData['parent_id'] ?? null,
                        'sort_order' => $itemData['sort_order'],
                    ]);
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Orden actualizado correctamente.',
        ]);
    }
}
