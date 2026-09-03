/**
 * Drag-and-drop product photo picker with an instant preview.
 */
export default function initImagePicker() {
    document.querySelectorAll('[data-image-picker]').forEach((picker) => {
        const input = picker.querySelector('input[type="file"]');
        const dropzone = picker.querySelector('[data-image-drop]');
        const preview = picker.querySelector('[data-image-preview]');

        if (!input || !dropzone) {
            return;
        }

        const showPreview = (file) => {
            if (!preview || !file || !file.type.startsWith('image/')) {
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                preview.src = event.target.result;
                preview.hidden = false;
            };
            reader.readAsDataURL(file);
        };

        dropzone.addEventListener('click', () => input.click());

        dropzone.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                input.click();
            }
        });

        ['dragenter', 'dragover'].forEach((type) => {
            dropzone.addEventListener(type, (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragging');
            });
        });

        ['dragleave', 'drop'].forEach((type) => {
            dropzone.addEventListener(type, (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragging');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];

            if (!file) {
                return;
            }

            // DataTransfer is the only way to programmatically set a file input.
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;

            showPreview(file);
        });

        input.addEventListener('change', () => showPreview(input.files?.[0]));
    });
}
