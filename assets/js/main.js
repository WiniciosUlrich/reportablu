document.addEventListener('DOMContentLoaded', function () {
    var alerts = document.querySelectorAll('.alert');
    if (alerts.length > 0) {
        window.setTimeout(function () {
            alerts.forEach(function (alertElement) {
                alertElement.style.transition = 'opacity 0.35s ease';
                alertElement.style.opacity = '0';

                window.setTimeout(function () {
                    if (alertElement.parentNode) {
                        alertElement.parentNode.removeChild(alertElement);
                    }
                }, 380);
            });
        }, 5000);
    }

    var fileInput = document.getElementById('arquivos');
    var filePreview = document.getElementById('arquivos-preview');

    if (fileInput && filePreview) {
        fileInput.addEventListener('change', function () {
            if (!fileInput.files || fileInput.files.length === 0) {
                filePreview.textContent = 'Nenhum arquivo selecionado.';
                return;
            }

            var listElement = document.createElement('ul');
            listElement.className = 'attachments-list';

            for (var index = 0; index < fileInput.files.length; index++) {
                var file = fileInput.files[index];
                var item = document.createElement('li');
                item.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                listElement.appendChild(item);
            }

            filePreview.innerHTML = '';
            filePreview.appendChild(listElement);
        });
    }

    var statusForm = document.querySelector('[data-status-form]');
    if (statusForm) {
        statusForm.addEventListener('submit', function (event) {
            var confirmed = window.confirm('Confirmar salvamento geral deste chamado?');
            if (!confirmed) {
                event.preventDefault();
            }
        });
    }
});

function formatFileSize(bytes) {
    if (bytes < 1024) {
        return String(bytes) + ' B';
    }

    if (bytes < 1048576) {
        return String(Math.round((bytes / 1024) * 100) / 100) + ' KB';
    }

    return String(Math.round((bytes / 1048576) * 100) / 100) + ' MB';
}