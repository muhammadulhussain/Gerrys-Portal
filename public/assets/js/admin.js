AOS.init({ duration: 700, easing: 'ease-out-cubic', once: true });

function animateCounts() {
    const elements = document.querySelectorAll('[data-target]');
    elements.forEach(el => {
        const target = parseInt(el.dataset.target || 0, 10);
        const duration = 900;
        let start = null;

        function step(timestamp) {
            if (!start) start = timestamp;
            const progress = timestamp - start;
            const val = Math.min(Math.floor((progress / duration) * target), target);
            el.textContent = val.toLocaleString();
            if (progress < duration) {
                requestAnimationFrame(step);
            }
        }
        requestAnimationFrame(step);
    });
}

document.addEventListener('DOMContentLoaded', animateCounts);

// ---------- Monthly Line Chart ----------
(function () {
    if (!window.dashboardData) return;

    const ctx = document.getElementById('customersChart')?.getContext('2d');
    if (!ctx) return;

    const gradient = ctx.createLinearGradient(0, 0, 0, 220);
    gradient.addColorStop(0, "rgba(54,162,235,0.35)");
    gradient.addColorStop(1, "rgba(54,162,235,0)");

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
            datasets: [{
                label: "New Customers",
                data: window.dashboardData.monthlyCustomers,
                borderColor: "rgba(54,162,235,1)",
                backgroundColor: gradient,
                borderWidth: 2.5,
                fill: true,
                tension: 0.36,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
})();

// ---------- Status Doughnut ----------
(function () {
    if (!window.dashboardData) return;

    const ctx2 = document.getElementById('customerChart')?.getContext('2d');
    if (!ctx2) return;

    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Suspended', 'Temp Off', 'Terminated'],
            datasets: [{
                data: [
                    window.dashboardData.customerStatus.active,
                    window.dashboardData.customerStatus.suspended,
                    window.dashboardData.customerStatus.tempOff,
                    window.dashboardData.customerStatus.terminated
                ],
                backgroundColor: ['#16a34a', '#0b5bd7', '#06b6d4', '#ef4444'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
})();
