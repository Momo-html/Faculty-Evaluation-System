document.addEventListener("DOMContentLoaded", function () {
    const dataContainer = document.getElementById("chart-data");
    if (!dataContainer) return;

    try {
        // Parse data from data-attributes
        const labels = JSON.parse(dataContainer.dataset.labels || "[]");
        const studentData = JSON.parse(dataContainer.dataset.students || "[]");
        const facultyData = JSON.parse(dataContainer.dataset.faculty || "[]");
        const velocityLabels = JSON.parse(
            dataContainer.dataset.velocityLabels || "[]",
        );
        const velocityData = JSON.parse(
            dataContainer.dataset.velocityData || "[]",
        );

        const hasData =
            labels.length > 0 &&
            (studentData.some((v) => v > 0) || facultyData.some((v) => v > 0));
        const palette = [
            "#2E5BFF", 
            "#8C54FF",  
            "#FF3D71",  
            "#FF9124", 
            "#00D68F", 
            "#00B3FF",  
            "#FFCC00",  
            "#FF5216",
            "#607D8B", 
            "#BA68C8",
            "#4DD0E1",
            "#795548", 
        ];

        // 1. Student Population Bar Chart
        const popCanvas = document.getElementById("popChart");
        if (popCanvas) {
            new Chart(popCanvas, {
                type: "bar",
                data: {
                    labels: hasData ? labels : ["No Data"],
                    datasets: [
                        {
                            label: "Students",
                            data: hasData ? studentData : [0],
                            backgroundColor: "#2e7d32",
                            borderRadius: 5,
                            barThickness: 30,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { display: false } },
                        x: { grid: { display: false } },
                    },
                },
            });
        }

        // 2. Faculty Distribution Doughnut Chart
        const deptCanvas = document.getElementById("deptChart");
        if (deptCanvas) {
            new Chart(deptCanvas, {
                type: "doughnut",
                data: {
                    labels: hasData ? labels : ["No Data"],
                    datasets: [
                        {
                            data: hasData ? facultyData : [1],
                            backgroundColor: hasData ? palette : ["#e0e0e0"],
                            hoverOffset: 15,
                            borderWidth: 2,
                            borderColor: "#ffffff",
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: "70%",
                    plugins: {
                        legend: {
                            display: hasData,
                            position: "right",
                            labels: {
                                usePointStyle: true,
                                boxWidth: 12,
                                padding: 10,
                                font: { size: 10 },
                            },
                        },
                    },
                },
            });
        }

        // 3. Descriptive Analytics: Response Velocity (Line Chart)
        const velocityCanvas = document.getElementById("velocityChart");
        if (velocityCanvas) {
            const ctx = velocityCanvas.getContext("2d");

            // Create a subtle gradient for the fill
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, "rgba(46, 125, 50, 0.2)");
            gradient.addColorStop(1, "rgba(46, 125, 50, 0)");

            new Chart(velocityCanvas, {
                type: "line",
                data: {
                    labels: velocityLabels,
                    datasets: [
                        {
                            label: "Daily Submissions",
                            data: velocityData,
                            borderColor: "#2e7d32",
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 4,
                            pointBackgroundColor: "#2e7d32",
                            borderWidth: 3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: "#f0f0f0" },
                            ticks: { font: { size: 10 } },
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } },
                        },
                    },
                },
            });
        }
    } catch (e) {
        console.error("Dashboard Chart Init Failed:", e);
    }
});
