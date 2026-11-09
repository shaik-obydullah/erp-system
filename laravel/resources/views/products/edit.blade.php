@extends('roles.layout')

@section('title', 'Edit Product')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
@include('layouts.partials.form-styles')
@endpush

@section('content')
<div class="card form-card" x-data="productForm()" x-init="init()">
    <div class="card-header">
        <div>
            <h2 class="card-title">Edit Product</h2>
            <p class="card-subtitle">Update the catalog details, media and visibility for this product.</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Back to Products</a>
    </div>
    <div class="card-body">

        <div x-show="successMessage" x-cloak class="alert alert-success show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                <polyline points="22 4 12 14.01 9 11.01" />
            </svg>
            <span x-text="successMessage"></span>
        </div>

        <div x-show="errorMessage" x-cloak class="alert alert-error show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="15" y1="9" x2="9" y2="15" />
                <line x1="9" y1="9" x2="15" y2="15" />
            </svg>
            <span x-text="errorMessage"></span>
        </div>

        <form @submit.prevent="submit($event)" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="remove_images" x-model="removeImages">

            <section class="form-section">
                <div class="section-heading">
                    <div class="section-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </div>
                    <div>
                        <h3 class="section-title">Basic information</h3>
                        <p class="section-hint">Name and classify the product so it is easy to find in the catalog.</p>
                    </div>
                </div>

                <div class="form-group">
                    <label for="name">Product name <span class="required">*</span></label>
                    <input type="text" id="name" x-model="form.name" value="{{ old('name', $product->name) }}" placeholder="e.g. Wireless Noise-Cancelling Headphones" required autofocus>
                    <span class="form-error" x-show="errors.name" x-text="errors.name"></span>
                </div>

                <div class="form-row form-row--three">
                    <div class="form-group">
                        <label for="fk_brand_id">Brand</label>
                        <select id="fk_brand_id" x-model="form.fk_brand_id">
                            <option value="">Select Brand</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" x-show="errors.fk_brand_id" x-text="errors.fk_brand_id"></span>
                    </div>
                    <div class="form-group">
                        <label for="fk_category_id">Category</label>
                        <select id="fk_category_id" x-model="form.fk_category_id" @change="form.fk_subcategory_id = ''">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" x-show="errors.fk_category_id" x-text="errors.fk_category_id"></span>
                    </div>
                    <div class="form-group">
                        <label for="fk_subcategory_id">Subcategory</label>
                        <select id="fk_subcategory_id" x-model="form.fk_subcategory_id" :disabled="!form.fk_category_id">
                            <option value="">Select Subcategory</option>
                            <template x-for="sub in availableSubcategories" :key="sub.id">
                                <option :value="sub.id" x-text="sub.name"></option>
                            </template>
                        </select>
                        <span class="form-error" x-show="errors.fk_subcategory_id" x-text="errors.fk_subcategory_id"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fk_supplier_id">Supplier</label>
                        <select id="fk_supplier_id" x-model="form.fk_supplier_id">
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    {{ $product->fk_supplier_id == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="form-error" x-show="errors.fk_supplier_id" x-text="errors.fk_supplier_id"></span>
                    </div>
                    <div class="form-group">
                        <label for="fk_unit_id">Unit</label>
                        <select id="fk_unit_id" x-model="form.fk_unit_id">
                            <option value="">Select Unit</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ $product->fk_unit_id == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->name }}</option>
                            @endforeach
                        </select>
                        <span class="form-error" x-show="errors.fk_unit_id" x-text="errors.fk_unit_id"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="sku">SKU</label>
                        <input type="text" id="sku" x-model="form.sku" value="{{ old('sku', $product->sku) }}" placeholder="e.g. AUD-WH-1000">
                        <span class="form-hint">Unique stock keeping unit. Optional.</span>
                        <span class="form-error" x-show="errors.sku" x-text="errors.sku"></span>
                    </div>
                    <div class="form-group">
                        <label for="barcode">Barcode</label>
                        <input type="text" id="barcode" x-model="form.barcode" value="{{ old('barcode', $product->barcode) }}" placeholder="e.g. 5012345678900">
                        <span class="form-hint">Used for scanning at the point of sale. Optional.</span>
                        <span class="form-error" x-show="errors.barcode" x-text="errors.barcode"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="size">Size</label>
                        <select id="size" x-model="form.size">
                            <option value="">Select Size</option>
                            @foreach($sizes as $size)
                                <option value="{{ $size->name }}">{{ $size->name }}</option>
                            @endforeach
                            @php($currentSize = old('size', $product->size))
                            @if($currentSize && !$sizes->contains('name', $currentSize))
                                <option value="{{ $currentSize }}">{{ $currentSize }} (current)</option>
                            @endif
                        </select>
                        <span class="form-hint">Options are managed in the Sizes module.</span>
                        <span class="form-error" x-show="errors.size" x-text="errors.size"></span>
                    </div>
                    <div class="form-group">
                        <label for="color">Color</label>
                        <select id="color" x-model="form.color">
                            <option value="">Select Color</option>
                            @foreach($colors as $color)
                                <option value="{{ $color->name }}">{{ $color->name }}</option>
                            @endforeach
                            @php($currentColor = old('color', $product->color))
                            @if($currentColor && !$colors->contains('name', $currentColor))
                                <option value="{{ $currentColor }}">{{ $currentColor }} (current)</option>
                            @endif
                        </select>
                        <span class="form-hint">Options are managed in the Colors module.</span>
                        <span class="form-error" x-show="errors.color" x-text="errors.color"></span>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="section-heading">
                    <div class="section-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                    <div>
                        <h3 class="section-title">Product images</h3>
                        <p class="section-hint">Upload clear images for the catalog and storefront. The first image is used as the cover.</p>
                    </div>
                </div>

                <div class="form-group">
                    <div class="image-upload-zone" @click="$refs.imageInput.click()" @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false" @drop.prevent="handleDrop($event); dragOver = false"
                        :class="{ 'dragover': dragOver }">
                        <div class="upload-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        </div>
                        <p class="upload-text">Drop images here or <span>browse files</span></p>
                        <p class="hint">JPEG, PNG, GIF or WebP &middot; Up to 2MB per image</p>
                    </div>
                    <input type="file" x-ref="imageInput" multiple accept="image/*" style="display:none"
                        @change="handleFiles($event)">
                    <span class="form-error" x-show="errors.images" x-text="errors.images"></span>
                    <div class="image-preview-grid" x-show="allImagePreviews.length > 0">
                        <template x-for="(preview, index) in allImagePreviews" :key="preview.key">
                            <div class="image-preview-item">
                                <img :src="preview.url" :alt="preview.name">
                                <button type="button" class="remove-btn" @click="removeImage(index)" aria-label="Remove image">&times;</button>
                                <div class="file-name" x-text="preview.name"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="section-heading">
                    <div class="section-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                    <div>
                        <h3 class="section-title">Description &amp; visibility</h3>
                        <p class="section-hint">Tell customers what makes this product special and control where it appears.</p>
                    </div>
                </div>

                <div class="form-group">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                        <label for="description">Description</label>
                        <button type="button" class="btn btn-secondary btn-sm"
                                @click="generateDescription()" :disabled="aiBusy">
                            <span x-show="!aiBusy">Generate with AI</span>
                            <span x-show="aiBusy">Generating…</span>
                        </button>
                    </div>
                    <textarea id="description" x-model="form.description"></textarea>
                    <span class="form-error" x-show="errors.description" x-text="errors.description"></span>
                </div>

                <div class="form-group narrow">
                    <label for="status">Status <span class="required">*</span></label>
                    <select id="status" x-model="form.status" required>
                        <option value="active" {{ $product->status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $product->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="archive" {{ $product->status == 'archive' ? 'selected' : '' }}>Archive</option>
                    </select>
                    <span class="form-hint">Inactive and archived products stay in the catalog but are hidden from sales.</span>
                    <span class="form-error" x-show="errors.status" x-text="errors.status"></span>
                </div>
            </section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Update Product</span>
                    <span x-show="submitting">Saving...</span>
                </button>
                <a href="{{ route('products.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script>
function productForm() {
    return {
        form: {
            name: @json(old('name', $product->name)),
            fk_brand_id: @json(old('fk_brand_id', $product->fk_brand_id)),
            fk_category_id: @json(old('fk_category_id', $product->fk_category_id)),
            fk_subcategory_id: @json(old('fk_subcategory_id', $product->fk_subcategory_id)),
            fk_supplier_id: @json(old('fk_supplier_id', $product->fk_supplier_id)),
            fk_unit_id: @json(old('fk_unit_id', $product->fk_unit_id)),
            sku: @json(old('sku', $product->sku)),
            barcode: @json(old('barcode', $product->barcode)),
            size: @json(old('size', $product->size)),
            color: @json(old('color', $product->color)),
            description: @json(old('description', $product->description)),
            status: @json(old('status', $product->status)),
        },
        categoriesData: @json($categories),
        errors: {},
        errorMessage: '',
        successMessage: '',
        submitting: false,
        csrfToken: '',
        existingImages: @json($product->parsed_images ?? []),
        removeImages: '',
        newImageFiles: [],
        imagePreviews: [],
        dragOver: false,
        aiBusy: false,

        get availableSubcategories() {
            if (!this.form.fk_category_id) return [];
            var cat = this.categoriesData.find(function(c) {
                return c.id == this.form.fk_category_id;
            }.bind(this));
            return cat ? cat.children : [];
        },

        get allImagePreviews() {
            var self = this;
            var existing = this.existingImages.map(function(img, i) {
                return {
                    url: '{{ asset("uploads/") }}/' + img,
                    name: img,
                    isNew: false,
                    key: 'existing-' + i,
                    index: i
                };
            });
            var newOnes = this.imagePreviews.map(function(img, i) {
                return {
                    url: img.url,
                    name: img.name,
                    isNew: true,
                    key: 'new-' + i,
                    index: i
                };
            });
            return existing.concat(newOnes);
        },

        init() {
            this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            var self = this;
            $('#description').summernote({
                height: 250,
                placeholder: 'Write product description here...',
                callbacks: {
                    onChange: function(contents) {
                        self.form.description = contents;
                    },
                    onImageUpload: function(files) {
                        self.uploadToEditor(files[0]);
                    }
                }
            });

            if (this.form.description) {
                $('#description').summernote('code', this.form.description);
            }
        },

        uploadToEditor(file) {
            var self = this;
            var formData = new FormData();
            formData.append('file', file);

            fetch('{{ route("products.upload-media") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: formData,
                })
                .then(function(r) {
                    if (!r.ok) {
                        return r.text().then(function() {
                            throw new Error('Upload failed with status ' + r.status);
                        });
                    }
                    return r.json();
                })
                .then(function(data) {
                    if (data.url) {
                        $('#description').summernote('insertImage', data.url);
                    }
                })
                .catch(function(err) {
                    self.errorMessage = 'Failed to upload image. ' + (err.message || '');
                });
        },

        handleFiles(event) {
            var files = event.target.files;
            this.addFiles(files);
            event.target.value = '';
        },

        handleDrop(event) {
            this.addFiles(event.dataTransfer.files);
        },

        addFiles(files) {
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                if (!file.type.startsWith('image/')) continue;
                if (file.size > 2 * 1024 * 1024) {
                    this.errorMessage = 'File "' + file.name + '" exceeds 2MB limit.';
                    continue;
                }
                this.newImageFiles.push(file);
                var reader = new FileReader();
                var self = this;
                reader.onload = (function(url, name) {
                    return function(e) {
                        self.imagePreviews.push({
                            url: e.target.result,
                            name: name
                        });
                    };
                })(URL.createObjectURL(file), file.name);
                reader.readAsDataURL(file);
            }
        },

        removeImage(index) {
            var existingCount = this.existingImages.length;
            if (index < existingCount) {
                var removeIdx = parseInt(index);
                this.removeImages = this.removeImages ? this.removeImages + ',' + removeIdx : '' + removeIdx;
                this.existingImages.splice(index, 1);
            } else {
                var newIndex = index - existingCount;
                this.imagePreviews.splice(newIndex, 1);
                this.newImageFiles.splice(newIndex, 1);
            }
        },

        async generateDescription() {
            if (!this.form.name.trim()) {
                this.errorMessage = 'Enter a product name first.';
                return;
            }

            this.aiBusy = true;
            this.errorMessage = '';

            try {
                const category = this.categoriesData.find(
                    (c) => String(c.id) === String(this.form.fk_category_id)
                );

                const { description } = await window.aiPost('/api/ai/product-description', {
                    name: this.form.name,
                    category: category ? category.name : '',
                    specs: $('#description').summernote('code'),
                });

                this.form.description = description;
                $('#description').summernote('code', description);
            } catch (e) {
                this.errorMessage = e.message;
            } finally {
                this.aiBusy = false;
            }
        },

        async submit() {
            this.errors = {};
            this.errorMessage = '';
            this.successMessage = '';
            this.submitting = true;
            this.form.description = $('#description').summernote('code');

            var formData = new FormData();
            formData.append('name', this.form.name);
            formData.append('status', this.form.status);
            formData.append('_method', 'PUT');

            if (this.removeImages) formData.append('remove_images', this.removeImages);
            if (this.form.fk_brand_id) formData.append('fk_brand_id', this.form.fk_brand_id);
            if (this.form.fk_category_id) formData.append('fk_category_id', this.form.fk_category_id);
            if (this.form.fk_subcategory_id) formData.append('fk_subcategory_id', this.form.fk_subcategory_id);
            if (this.form.fk_supplier_id) formData.append('fk_supplier_id', this.form.fk_supplier_id);
            if (this.form.fk_unit_id) formData.append('fk_unit_id', this.form.fk_unit_id);
            if (this.form.sku) formData.append('sku', this.form.sku);
            if (this.form.barcode) formData.append('barcode', this.form.barcode);
            if (this.form.size) formData.append('size', this.form.size);
            if (this.form.color) formData.append('color', this.form.color);
            if (this.form.description) formData.append('description', this.form.description);

            for (var i = 0; i < this.newImageFiles.length; i++) {
                formData.append('images[]', this.newImageFiles[i]);
            }

            try {
                var response = await fetch('{{ route("products.update", $product) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData,
                });
                var data = await response.json();
                if (response.ok) {
                    window.location.href = data.redirect || '{{ route("products.index") }}';
                    return;
                }
                if (data.errors) {
                    this.errors = {};
                    for (var key in data.errors) {
                        this.errors[key] = data.errors[key][0];
                    }
                } else if (data.message) {
                    this.errorMessage = data.message;
                }
            } catch (e) {
                this.errorMessage = 'An unexpected error occurred. Please try again.';
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>
@endpush
