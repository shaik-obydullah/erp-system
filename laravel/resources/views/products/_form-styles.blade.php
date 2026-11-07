<style>
    .product-form .card-header {
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
    }
    .product-form .card-title {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: -0.01em;
    }
    .product-form .card-subtitle {
        margin-top: 4px;
        font-size: 13px;
        color: var(--text-secondary);
    }
    .product-form .card-body {
        padding: 8px 24px 24px;
    }
    .product-form .form-section {
        padding: 24px 0;
    }
    .product-form .form-section + .form-section {
        border-top: 1px solid var(--border);
    }
    .product-form .section-heading {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 20px;
    }
    .product-form .section-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #e8f0fe;
        color: var(--primary);
    }
    .product-form .section-icon svg {
        width: 19px;
        height: 19px;
    }
    .product-form .section-title {
        font-size: 15px;
        font-weight: 600;
        line-height: 1.35;
    }
    .product-form .section-hint {
        margin-top: 2px;
        font-size: 12.5px;
        color: var(--text-secondary);
    }
    .product-form .form-group {
        margin-bottom: 18px;
    }
    .product-form .form-row .form-group {
        margin-bottom: 18px;
    }
    .product-form .form-section > .form-group:last-child,
    .product-form .form-section > .form-row:last-child .form-group {
        margin-bottom: 0;
    }
    .product-form .required {
        color: var(--error);
    }
    .product-form .form-hint {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        color: var(--text-secondary);
    }
    .product-form .form-group.narrow {
        max-width: 320px;
    }
    .product-form .form-row--three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .product-form .form-group input:focus,
    .product-form .form-group select:focus,
    .product-form .form-group textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
    }
    .product-form .form-group input::placeholder,
    .product-form .form-group textarea::placeholder {
        color: var(--text-disabled);
    }
    .product-form .form-group select:disabled {
        background: var(--bg-gray);
        color: var(--text-disabled);
        cursor: not-allowed;
    }
    .product-form .form-actions {
        margin-top: 0;
    }
    .product-form .form-actions .btn {
        min-width: 132px;
        justify-content: center;
    }
    .product-form .form-actions .btn-secondary {
        min-width: 96px;
    }
    .image-upload-zone {
        position: relative;
        border: 1.5px dashed #cbd5e1;
        border-radius: 12px;
        padding: 28px 20px;
        text-align: center;
        cursor: pointer;
        transition: border-color var(--transition), background var(--transition), box-shadow var(--transition), transform var(--transition);
        background: linear-gradient(180deg, #fbfdff 0%, #f8fafc 100%);
    }
    .image-upload-zone:hover,
    .image-upload-zone.dragover {
        border-color: var(--primary);
        background: #f0f7ff;
        box-shadow: 0 0 0 4px rgba(26, 115, 232, 0.08);
    }
    .image-upload-zone.dragover {
        transform: translateY(-1px);
    }
    .image-upload-zone .upload-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        margin: 0 auto 12px;
        border-radius: 50%;
        background: var(--bg-white);
        border: 1px solid var(--border);
        color: var(--text-secondary);
        transition: color var(--transition), border-color var(--transition), transform var(--transition);
    }
    .image-upload-zone:hover .upload-icon,
    .image-upload-zone.dragover .upload-icon {
        color: var(--primary);
        border-color: #a8c7fa;
        transform: scale(1.04);
    }
    .image-upload-zone .upload-icon svg {
        width: 26px;
        height: 26px;
    }
    .image-upload-zone p {
        margin: 0;
    }
    .image-upload-zone .upload-text {
        font-size: 14px;
        font-weight: 500;
        color: var(--text-primary);
    }
    .image-upload-zone .upload-text span {
        color: var(--primary);
    }
    .image-upload-zone .hint {
        margin-top: 4px;
        font-size: 12px;
        color: var(--text-secondary);
    }
    .image-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(112px, 1fr));
        gap: 12px;
        margin-top: 14px;
    }
    .image-preview-item {
        position: relative;
        overflow: hidden;
        aspect-ratio: 1;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--bg-gray);
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
    }
    .image-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .image-preview-item .remove-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border: none;
        border-radius: 50%;
        background: rgba(217, 48, 37, 0.92);
        color: #fff;
        font-size: 15px;
        line-height: 1;
        cursor: pointer;
        opacity: 0;
        transition: opacity var(--transition), transform var(--transition);
    }
    .image-preview-item:hover .remove-btn,
    .image-preview-item .remove-btn:focus-visible {
        opacity: 1;
    }
    .image-preview-item .remove-btn:hover {
        transform: scale(1.08);
    }
    .image-preview-item .file-name {
        position: absolute;
        right: 0;
        bottom: 0;
        left: 0;
        padding: 3px 6px;
        overflow: hidden;
        background: rgba(0, 0, 0, 0.62);
        color: #fff;
        font-size: 10px;
        white-space: nowrap;
        text-overflow: ellipsis;
    }
    .note-editor {
        border-radius: 8px !important;
        border: 1px solid var(--border) !important;
    }
    .note-toolbar {
        border-radius: 8px 8px 0 0 !important;
        background: var(--bg-gray) !important;
    }
    .note-editing-area {
        border-radius: 0 0 8px 8px !important;
    }
    @media (hover: none) {
        .image-preview-item .remove-btn {
            opacity: 1;
        }
    }
    @media (max-width: 768px) {
        .product-form .card-body {
            padding: 4px 16px 20px;
        }
        .product-form .form-section {
            padding: 20px 0;
        }
        .product-form .form-group.narrow {
            max-width: none;
        }
        .product-form .form-row--three {
            grid-template-columns: 1fr;
        }
        .product-form .form-actions {
            flex-wrap: wrap;
        }
        .product-form .form-actions .btn {
            flex: 1 1 auto;
        }
    }
</style>
