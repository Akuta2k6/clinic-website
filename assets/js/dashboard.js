(function () {
    var sidebar = document.getElementById('dashboardSidebar');
    var backdrop = document.querySelector('[data-sidebar-close]');
    var toggle = document.querySelector('[data-sidebar-toggle]');

    if (!sidebar || !backdrop || !toggle) {
        return;
    }

    function closeSidebar() {
        sidebar.classList.remove('is-open');
        backdrop.classList.remove('is-visible');
        document.body.classList.remove('sidebar-open');
    }

    toggle.addEventListener('click', function () {
        sidebar.classList.toggle('is-open');
        backdrop.classList.toggle('is-visible');
        document.body.classList.toggle('sidebar-open');
    });

    backdrop.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });
})();
