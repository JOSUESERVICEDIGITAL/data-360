<script>
document.addEventListener('DOMContentLoaded', function () {
    const mobileBtn = document.getElementById('mobileBtn');
    const nav = document.getElementById('nav');

    if (mobileBtn && nav) {
        mobileBtn.addEventListener('click', function () {
            nav.classList.toggle('active');
        });
    }
});
</script>