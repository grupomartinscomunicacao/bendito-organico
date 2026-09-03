@php
    /** @var \App\Models\Product $product */
    $isEdit = $product->exists;
@endphp

<div class="row g-3">

    {{-- Main column --}}
    <div class="col-lg-8">
        <x-admin.card title="Informações do produto">
            <x-form.input
                name="name"
                label="Nome"
                :value="$product->name"
                placeholder="Alface Crespa"
                maxlength="150"
                required
                autofocus
            />

            <x-form.input
                name="slug"
                label="URL amigável"
                :value="$product->slug"
                placeholder="alface-crespa"
                maxlength="170"
                hint="Deixe em branco para gerar automaticamente a partir do nome. Ex.: /produtos/alface-crespa"
            />

            <x-form.input
                name="short_description"
                label="Descrição curta"
                :value="$product->short_description"
                placeholder="Folhas macias e crocantes, colhidas na manhã da entrega."
                maxlength="180"
                hint="Aparece no card do produto e nos resultados de busca."
            />

            <x-form.textarea
                name="description"
                label="Descrição completa"
                :value="$product->description"
                rows="8"
                maxlength="5000"
                hint="Separe os parágrafos com uma linha em branco."
            />
        </x-admin.card>

        <x-admin.card title="Preço e estoque" class="mt-3">
            <div class="row">
                <div class="col-md-4">
                    <x-form.input
                        name="price"
                        label="Preço de venda"
                        :value="$product->price ? \App\Support\Money::decimal($product->price) : null"
                        placeholder="5,90"
                        inputmode="decimal"
                        data-mask="money"
                        required
                    />
                </div>

                <div class="col-md-4">
                    <x-form.input
                        name="compare_at_price"
                        label="Preço comparativo"
                        :value="$product->compare_at_price ? \App\Support\Money::decimal($product->compare_at_price) : null"
                        placeholder="7,90"
                        inputmode="decimal"
                        data-mask="money"
                        hint="Opcional. Mostra o desconto no card."
                    />
                </div>

                <div class="col-md-4">
                    <x-form.select
                        name="unit"
                        label="Unidade de venda"
                        :options="$units"
                        :value="$product->unit?->value"
                        required
                    />
                </div>

                <div class="col-md-4">
                    <x-form.input
                        name="stock"
                        label="Estoque"
                        :value="\App\Support\Money::quantity($product->stock ?? 0)"
                        inputmode="decimal"
                        required
                    />
                </div>

                <div class="col-md-8 d-flex align-items-center">
                    <x-form.checkbox
                        name="track_stock"
                        label="Controlar estoque deste produto"
                        :checked="$product->track_stock ?? true"
                        hint="Desmarque para produtos que nunca esgotam."
                        switch
                        class="mb-0"
                    />
                </div>
            </div>
        </x-admin.card>

        <x-admin.card title="SEO" class="mt-3">
            <x-form.input
                name="meta_title"
                label="Título da página"
                :value="$product->meta_title"
                maxlength="255"
                hint="Deixe em branco para usar o nome do produto."
            />

            <x-form.textarea
                name="meta_description"
                label="Meta description"
                :value="$product->meta_description"
                rows="2"
                maxlength="300"
                class="mb-0"
            />
        </x-admin.card>
    </div>

    {{-- Side column --}}
    <div class="col-lg-4">
        <x-admin.card title="Publicação">
            <x-form.checkbox
                name="is_active"
                label="Produto visível na loja"
                :checked="$product->is_active ?? true"
                switch
            />

            <x-form.checkbox
                name="is_featured"
                label="Destacar na home"
                :checked="$product->is_featured ?? false"
                switch
            />

            <x-form.input
                name="sort_order"
                type="number"
                label="Ordem de exibição"
                :value="$product->sort_order ?? 0"
                min="0"
                max="65535"
                hint="Menor número aparece primeiro."
                class="mb-0"
            />
        </x-admin.card>

        <x-admin.card title="Foto do produto" class="mt-3">
            <div data-image-picker>
                <img
                    src="{{ $product->image ? $product->image_url : '' }}"
                    alt=""
                    class="image-preview mb-3 mx-auto d-block"
                    data-image-preview
                    @unless ($product->image) hidden @endunless
                >

                <div
                    class="image-drop"
                    data-image-drop
                    role="button"
                    tabindex="0"
                    aria-label="Selecionar foto do produto"
                >
                    <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                    <span class="fw-semibold">Arraste a foto ou clique aqui</span>
                    <small>JPG, PNG ou WebP · mínimo 200×200 · até 4 MB</small>
                </div>

                <input
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                    class="d-none @error('image') is-invalid @enderror"
                >

                @error('image')
                    <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                @enderror

                @if ($isEdit && $product->image)
                    <p class="form-hint text-center mt-2 mb-0">
                        Enviar uma nova foto substitui a atual.
                    </p>
                @endif
            </div>
        </x-admin.card>

        <div class="d-grid gap-2 mt-3">
            <x-button variant="primary" size="lg" icon="check2">
                {{ $isEdit ? 'Salvar alterações' : 'Cadastrar produto' }}
            </x-button>

            <x-button href="{{ route('admin.products.index') }}" variant="outline-secondary">
                Cancelar
            </x-button>
        </div>
    </div>
</div>
