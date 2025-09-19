(function () {
    const table = document.querySelector('[data-comments-table]');
    if (!table) {
        return;
    }

    table.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-delete-comment]');
        if (!button) {
            return;
        }

        const row = button.closest('tr');
        const id = button.getAttribute('data-id');
        if (!id || !row) {
            return;
        }

        if (!confirm('Hapus komentar ini?')) {
            return;
        }

        try {
            const form = new FormData();
            form.append('id', id);
            const response = await fetch('backend/delete_comment.php', {
                method: 'POST',
                body: form,
            });
            const data = await response.json();
            if (data.success) {
                row.remove();
            } else {
                alert(data.message || 'Gagal menghapus komentar.');
            }
        } catch (error) {
            console.error(error);
            alert('Terjadi kesalahan saat menghapus komentar.');
        }
    });
})();
