document.addEventListener('DOMContentLoaded', function() {
    const saveBtn = document.getElementById('saveChanges');
    const heritageUpload = document.getElementById('uploadHeritage');
    const heritageImg = document.getElementById('heritageImg');
    const statusMsg = document.getElementById('statusMsg');

    // 1. Xử lý đổi ảnh (Preview)
    if (heritageUpload) {
        heritageUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    heritageImg.src = event.target.result; // Hiển thị ảnh mới ngay lập tức
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 2. Xử lý Lưu dữ liệu
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            saveBtn.disabled = true;
            statusMsg.textContent = "Saving...";

            // Thu thập tất cả các nội dung đã sửa
            const editables = document.querySelectorAll('.editable');
            const data = {};
            
            editables.forEach(el => {
                const id = el.getAttribute('data-id');
                data[id] = el.innerText;
            });

            console.log("Data to send to server:", data);

            // Gửi dữ liệu bằng Fetch API tới file PHP xử lý (Ví dụ: update-about.php)
            /*
            fetch('../php/update-about.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(result => {
                statusMsg.textContent = "All changes saved!";
                saveBtn.disabled = false;
            });
            */
            
            // Giả lập lưu thành công
            setTimeout(() => {
                statusMsg.textContent = "Changes saved locally!";
                saveBtn.disabled = false;
            }, 1000);
        });
    }
});