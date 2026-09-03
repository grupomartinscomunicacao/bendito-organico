<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores a product photo on the public disk and cleans up the one it replaces.
 *
 * The filename is generated, never taken from the upload: an attacker-supplied
 * name is the classic way a "photo" ends up executable or escapes its folder.
 */
class StoreProductImage
{
    public function handle(Product $product, UploadedFile $file): string
    {
        $name = sprintf(
            '%s-%s.%s',
            Str::slug($product->name) ?: 'produto',
            Str::random(8),
            Str::lower($file->extension() ?: 'jpg'),
        );

        $path = $file->storeAs('products', $name, 'public');

        $this->deleteExisting($product);

        return $path;
    }

    /**
     * Removes a product's stored photo. Demo images shipped in public/ are
     * left alone: they belong to the repository, not to the upload disk.
     */
    public function deleteExisting(Product $product): void
    {
        $current = $product->getOriginal('image');

        if (blank($current) || str_starts_with((string) $current, 'images/')) {
            return;
        }

        Storage::disk('public')->delete((string) $current);
    }
}
