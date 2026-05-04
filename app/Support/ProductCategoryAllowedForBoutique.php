<?php

namespace App\Support;

use App\Models\Boutique;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

/**
 * Aligné sur l’app mobile (règle B) : une catégorie produit est autorisée si elle est
 * l’une des catégories attachées à la boutique ou un descendant (chaîne parent_id).
 */
final class ProductCategoryAllowedForBoutique
{
    public static function check(?Boutique $boutique, int $categoryId): bool
    {
        if ($boutique === null) {
            return false;
        }

        // Pivot `boutique_category` a aussi une colonne `id` : pluck('id') sur la relation est ambigu (SQL 1052).
        $boutiqueCategoryIds = array_map(
            'intval',
            DB::table('boutique_category')
                ->where('boutique_id', $boutique->id)
                ->pluck('category_id')
                ->all()
        );
        if ($boutiqueCategoryIds === []) {
            return false;
        }

        $allowedSet = array_flip($boutiqueCategoryIds);

        /** @var array<int, int|null> $parentById */
        $parentById = Category::query()->pluck('parent_id', 'id')->all();

        $currentId = $categoryId;
        $visited = [];

        while ($currentId !== null) {
            if (isset($visited[$currentId])) {
                break;
            }
            $visited[$currentId] = true;

            if (! array_key_exists($currentId, $parentById)) {
                return false;
            }

            if (isset($allowedSet[$currentId])) {
                return true;
            }

            $parentRaw = $parentById[$currentId];
            $currentId = $parentRaw !== null ? (int) $parentRaw : null;
        }

        return false;
    }
}
