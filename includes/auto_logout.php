<script>


(function () {
    let timer;

    function logout() {
        window.location.href = "auto_logout.php";
    }

    function resetTimer() {
        clearTimeout(timer);
        timer = setTimeout(logout, 1 * 60 * 1000); 
    }

    window.addEventListener("load", resetTimer);
    document.addEventListener("mousemove", resetTimer);
    document.addEventListener("keydown", resetTimer);
    document.addEventListener("click", resetTimer);
    document.addEventListener("scroll", resetTimer);
})();
</script>
