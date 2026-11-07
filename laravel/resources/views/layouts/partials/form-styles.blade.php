<style>
    .form-card .card-header {
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
    }
    .form-card .card-title {
        font-size: 18px;
        font-weight: 600;
        letter-spacing: -0.01em;
    }
    .form-card .card-subtitle {
        margin-top: 4px;
        font-size: 13px;
        color: var(--text-secondary);
    }
    .form-card .card-body {
        padding: 8px 24px 24px;
    }
    .form-card .form-section {
        padding: 24px 0;
    }
    .form-card .form-section + .form-section {
        border-top: 1px solid var(--border);
    }
    .form-card .section-heading {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 20px;
    }
    .form-card .section-icon {
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
    .form-card .section-icon svg {
        width: 19px;
        height: 19px;
    }
    .form-card .section-title {
        font-size: 15px;
        font-weight: 600;
        line-height: 1.35;
    }
    .form-card .section-hint {
        margin-top: 2px;
        font-size: 12.5px;
        color: var(--text-secondary);
    }
    .form-card .form-group {
        margin-bottom: 18px;
    }
    .form-card .form-row .form-group {
        margin-bottom: 18px;
    }
    .form-card .form-section > .form-group:last-child,
    .form-card .form-section > .form-row:last-child .form-group {
        margin-bottom: 0;
    }
    .form-card .required {
        color: var(--error);
    }
    .form-card .form-hint {
        display: block;
        margin-top: 5px;
        font-size: 12px;
        color: var(--text-secondary);
    }
    .form-card .form-group.narrow {
        max-width: 320px;
    }
    .form-card .form-row--three {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .form-card .form-group input:focus,
    .form-card .form-group select:focus,
    .form-card .form-group textarea:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
    }
    .form-card .form-group input::placeholder,
    .form-card .form-group textarea::placeholder {
        color: var(--text-disabled);
    }
    .form-card .form-group select:disabled {
        background: var(--bg-gray);
        color: var(--text-disabled);
        cursor: not-allowed;
    }
    .form-card .searchable-select {
        position: relative;
    }
    .form-card .searchable-select input {
        padding-right: 40px;
    }
    .form-card .searchable-select .select-chevron {
        position: absolute;
        top: 50%;
        right: 14px;
        width: 16px;
        height: 16px;
        color: var(--text-secondary);
        pointer-events: none;
        transform: translateY(-50%);
    }
    .form-card .searchable-select .clear-btn {
        position: absolute;
        top: 50%;
        right: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        padding: 0;
        border: none;
        border-radius: 50%;
        background: var(--bg-gray);
        color: var(--text-secondary);
        font-size: 15px;
        line-height: 1;
        cursor: pointer;
        transform: translateY(-50%);
    }
    .form-card .searchable-select .clear-btn:hover {
        background: #e8eaed;
        color: var(--text-primary);
    }
    .form-card .searchable-options {
        position: absolute;
        z-index: 50;
        top: calc(100% + 4px);
        right: 0;
        left: 0;
        max-height: 280px;
        padding: 6px;
        overflow-y: auto;
        background: var(--bg-white);
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 10px 28px rgba(0, 0, 0, 0.14);
    }
    .form-card .searchable-option {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 2px;
        width: 100%;
        padding: 9px 12px;
        border: none;
        border-radius: 6px;
        background: transparent;
        text-align: left;
        cursor: pointer;
    }
    .form-card .searchable-option:hover,
    .form-card .searchable-option:focus-visible,
    .form-card .searchable-option.is-selected {
        background: #f0f7ff;
    }
    .form-card .searchable-option .option-name {
        font-size: 13.5px;
        font-weight: 500;
        color: var(--text-primary);
    }
    .form-card .searchable-option .option-meta {
        font-size: 11.5px;
        color: var(--text-secondary);
    }
    .form-card .searchable-empty {
        padding: 14px 12px;
        font-size: 13px;
        color: var(--text-secondary);
        text-align: center;
    }
    .form-card .form-actions {
        margin-top: 0;
    }
    .form-card .form-actions .btn {
        min-width: 132px;
        justify-content: center;
    }
    .form-card .form-actions .btn-secondary {
        min-width: 96px;
    }
    .form-card .form-note {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-top: 4px;
        padding: 12px 14px;
        border: 1px solid #d2e3fc;
        border-radius: 8px;
        background: #f0f7ff;
        font-size: 12.5px;
        line-height: 1.5;
        color: var(--text-secondary);
    }
    .form-card .form-note svg {
        flex: 0 0 auto;
        width: 16px;
        height: 16px;
        margin-top: 1px;
        color: var(--primary);
    }
    .form-card .impact-preview {
        margin-top: 20px;
        padding: 16px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: var(--bg-gray);
    }
    .form-card .impact-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }
    .form-card .impact-title {
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-primary);
    }
    .form-card .impact-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .form-card .impact-badge.is-out {
        background: #fef2f2;
        color: #b91c1c;
    }
    .form-card .impact-badge.is-in {
        background: #ecfdf5;
        color: #047857;
    }
    .form-card .impact-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 20px;
    }
    .form-card .impact-label {
        display: block;
        margin-bottom: 2px;
        font-size: 11px;
        font-weight: 500;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--text-secondary);
    }
    .form-card .impact-value {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
    }
    .form-card .impact-hint {
        margin-top: 14px;
        font-size: 12px;
        line-height: 1.5;
        color: var(--text-secondary);
    }
    @media (max-width: 640px) {
        .form-card .impact-grid {
            grid-template-columns: 1fr;
        }
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
        .form-card .card-body {
            padding: 4px 16px 20px;
        }
        .form-card .form-section {
            padding: 20px 0;
        }
        .form-card .form-group.narrow {
            max-width: none;
        }
        .form-card .form-row--three {
            grid-template-columns: 1fr;
        }
        .form-card .form-actions {
            flex-wrap: wrap;
        }
        .form-card .form-actions .btn {
            flex: 1 1 auto;
        }
    }
</style>
