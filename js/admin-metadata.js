(function () {
    const button = document.querySelector('[data-fetch-metadata]');
    const input = document.querySelector('[data-metadata-title]');
    if (!button || !input) {
        return;
    }

    const fields = {
        title: document.querySelector('input[name="title"]'),
        poster: document.querySelector('input[name="poster"]'),
        genres: document.querySelector('input[name="genres"]'),
        rating: document.querySelector('input[name="rating"]'),
        episodes: document.querySelector('input[name="episodes"]'),
        year: document.querySelector('input[name="year"]'),
        status: document.querySelector('input[name="status"]'),
        description: document.querySelector('textarea[name="description"]')
    };

    function fillForm(data) {
        if (fields.title && !fields.title.value) fields.title.value = data.title;
        if (fields.poster && !fields.poster.value && data.poster) fields.poster.value = data.poster;
        if (fields.genres && !fields.genres.value) fields.genres.value = data.genres;
        if (fields.rating && !fields.rating.value) fields.rating.value = data.rating;
        if (fields.episodes && !fields.episodes.value) fields.episodes.value = data.episodes;
        if (fields.year && !fields.year.value) fields.year.value = data.year;
        if (fields.status && !fields.status.value) fields.status.value = data.status;
        if (fields.description && !fields.description.value) fields.description.value = data.description;
    }

    button.addEventListener('click', async () => {
        const query = input.value.trim();
        if (query === '') {
            alert('Masukkan judul yang ingin dicari.');
            return;
        }

        try {
            button.disabled = true;
            button.textContent = 'Mengambil...';
            const response = await fetch(`backend/fetch_metadata.php?q=${encodeURIComponent(query)}`);
            const payload = await response.json();
            if (payload.success) {
                fillForm(payload.data);
            } else {
                alert(payload.message || 'Data tidak ditemukan.');
            }
        } catch (error) {
            console.error(error);
            alert('Gagal mengambil metadata.');
        } finally {
            button.disabled = false;
            button.textContent = 'Isi Otomatis';
        }
    });
})();
