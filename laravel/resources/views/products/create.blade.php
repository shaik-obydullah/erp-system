@extends('roles.layout')

@section('title', 'Create Product')

@push('head')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<style>
    .image-upload-zone {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 32px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s;
        background: #f9fafb;
    }
    .image-upload-zone:hover, .image-upload-zone.dragover {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    .image-upload-zone svg { width: 48px; height: 48px; margin: 0 auto 12px; color: #9ca3af; }
    .image-upload-zone p { color: #6b7280; margin: 0; }
    .image-upload-zone .hint { font-size: 12px; color: #9ca3af; margin-top: 4px; }
    .image-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }
    .image-preview-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        aspect-ratio: 1;
    }
    .image-preview-item img { width: 100%; height: 100%; object-fit: cover; }
    .image-preview-item .remove-btn {
        position: absolute; top: 4px; right: 4px;
        width: 24px; height: 24px; border-radius: 50%;
        background: #ef4444; color: white; border: none; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 14px; line-height: 1; opacity: 0; transition: opacity 0.2s;
    }
    .image-preview-item:hover .remove-btn { opacity: 1; }
    .image-preview-item .file-name {
        position: absolute; bottom: 0; left: 0; right: 0;
        background: rgba(0,0,0,0.6); color: white; font-size: 10px;
        padding: 2px 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .note-editor { border-radius: 8px !important; border: 1px solid #d1d5db !important; }
    .note-toolbar { border-radius: 8px 8px 0 0 !important; background: #f9fafb !important; }
    .note-editing-area { border-radius: 0 0 8px 8px !important; }
</style>
@endpush

@section('content')
<div class="card" x-data="productForm()" x-init="init()">
    <div class="card-header">
        <h2 class="card-title">Create Product</h2>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">Back to Products</a>
    </div>
    <div class="card-body">

        <div x-show="successMessage" x-cloak class="alert alert-success show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <span x-text="successMessage"></span>
        </div>

        <div x-show="errorMessage" x-cloak class="alert alert-error show">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <span x-text="errorMessage"></span>
        </div>

        <form @submit.prevent="submit($event)" enctype="multipart/form-data">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" x-model="form.name" required autofocus>
                    <span class="form-error" x-show="errors.name" x-text="errors.name"></span>
                </div>
            </div>

            <div class="form-row">
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
                    <select id="fk_category_id" x-model="form.fk_category_id">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_category_id" x-text="errors.fk_category_id"></span>
                </div>
            </div>

            <div class="form-row">
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
                <div class="form-group">
                    <label for="fk_unit_id">Unit</label>
                    <select id="fk_unit_id" x-model="form.fk_unit_id">
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <span class="form-error" x-show="errors.fk_unit_id" x-text="errors.fk_unit_id"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="sku">SKU</label>
                    <input type="text" id="sku" x-model="form.sku">
                    <span class="form-error" x-show="errors.sku" x-text="errors.sku"></span>
                </div>
                <div class="form-group">
                    <label for="barcode">Barcode</label>
                    <input type="text" id="barcode" x-model="form.barcode">
                    <span class="form-error" x-show="errors.barcode" x-text="errors.barcode"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="size">Size</label>
                    <input type="text" id="size" x-model="form.size">
                    <span class="form-error" x-show="errors.size" x-text="errors.size"></span>
                </div>
                <div class="form-group">
                    <label for="color">Color</label>
                    <input type="text" id="color" x-model="form.color">
                    <span class="form-error" x-show="errors.color" x-text="errors.color"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Product Images</label>
                    <div class="image-upload-zone"
                         @click="$refs.imageInput.click()"
                         @dragover.prevent="dragOver = true"
                         @dragleave.prevent="dragOver = false"
                         @drop.prevent="handleDrop($event); dragOver = false"
                         :class="{ 'dragover': dragOver }">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <p>Drop images here or click to browse</p>
                        <p class="hint">JPEG, PNG, GIF, WebP - Max 2MB each</p>
                    </div>
                    <input type="file" x-ref="imageInput" multiple accept="image/*" style="display:none" @change="handleFiles($event)">
                    <span class="form-error" x-show="errors.images" x-text="errors.images"></span>
                    <div class="image-preview-grid" x-show="imagePreviews.length > 0">
                        <template x-for="(preview, index) in imagePreviews" :key="index">
                            <div class="image-preview-item">
                                <img :src="preview.url" :alt="preview.name">
                                <button type="button" class="remove-btn" @click="removeNewImage(index)">&times;</button>
                                <div class="file-name" x-text="preview.name"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="description" x-model="form.description"></textarea>
                    <span class="form-error" x-show="errors.description" x-text="errors.description"></span>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" x-model="form.status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="archive">Archive</option>
                    </select>
                    <span class="form-error" x-show="errors.status" x-text="errors.status"></span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" :disabled="submitting">
                    <span x-show="!submitting">Create Product</span>
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
                name: '', fk_brand_id: '', fk_category_id: '', fk_subcategory_id: '', fk_supplier_id: '',
                fk_unit_id: '', sku: '', barcode: '', size: '', color: '',
                description: '', status: 'active',
            },
            categoriesData: @json($categories),
            errors: {},
            errorMessage: '',
            successMessage: '',
            submitting: false,
            csrfToken: '',
            newImageFiles: [],
            imagePreviews: [],
            dragOver: false,

            get availableSubcategories() {
                if (!this.form.fk_category_id) return [];
                var cat = this.categoriesData.find(function(c) { return c.id == this.form.fk_category_id; }.bind(this));
                return cat ? cat.children : [];
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
            },

            uploadToEditor(file) {
                var self = this;
                var formData = new FormData();
                formData.append('file', file);

                fetch('{{ route("products.upload-media") }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken },
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
                            self.imagePreviews.push({ url: e.target.result, name: name });
                        };
                    })(URL.createObjectURL(file), file.name);
                    reader.readAsDataURL(file);
                }
            },

            removeNewImage(index) {
                this.imagePreviews.splice(index, 1);
                this.newImageFiles.splice(index, 1);
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
                formData.append('_method', 'POST');

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
                    var response = await fetch('{{ route("products.store") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                        body: formData,
                    });
                    var data = await response.json();
                    if (response.ok) {
                        window.location.href = data.redirect || '{{ route("products.index") }}';
                        return;
                    }
                    if (data.errors) {
                        this.errors = {};
                        for (var key in data.errors) { this.errors[key] = data.errors[key][0]; }
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
