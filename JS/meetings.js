document.addEventListener('DOMContentLoaded', () => {
    // Object to store meetings
    const meetings = {
        January: [0, 0, 0, 0],
        February: [0, 0, 0, 0],
        March: [0, 0, 0, 0],
        April: [0, 0, 0, 0],
        May: [0, 0, 0, 0],
        June: [0, 0, 0, 0],
        July: [0, 0, 0, 0],
        August: [0, 0, 0, 0],
        September: [0, 0, 0, 0],
        October: [0, 0, 0, 0],
        November: [0, 0, 0, 0],
        December: [0, 0, 0, 0],
    };

    // Object to store unique meetings
    const uniqueMeetings = {};

    function calculateMeetingDuration(startTime, endTime) {
        const start = parseTime(startTime);
        const end = parseTime(endTime);

        // Calculate total minutes
        const durationMinutes = (end.hours * 60 + end.minutes) - (start.hours * 60 + start.minutes);

        // Convert minutes to hours using the given formula
        const durationHours = (durationMinutes * 0.5) / 30;

        // Debugging output
        console.log(`Start Time: ${startTime}, End Time: ${endTime}`);
        console.log(`Duration Minutes: ${durationMinutes}`);
        console.log(`Calculated Duration (Hours): ${durationHours}`);

        return durationHours; // No need to round in this case
    }

    function parseTime(timeString) {
        const [hours, minutes] = timeString.split(':').map(Number);
        return { hours, minutes };
    }

    document.getElementById('addMeetingButton').addEventListener('click', function() {
        const month = document.getElementById('month').value;
        const week = parseInt(document.getElementById('week').value);
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;
        const duration = calculateMeetingDuration(startTime, endTime);

        // Create a unique key for each meeting
        const meetingKey = `${month}-${week}-${startTime}-${endTime}`;

        // Check if the meeting is already added
        if (!uniqueMeetings[meetingKey]) {
            uniqueMeetings[meetingKey] = duration;
            addMeeting(month, week, duration);
            updateChart();
        } else {
            console.log(`Meeting already added: ${meetingKey}`);
        }
    });

    function addMeeting(month, week, duration) {
        if (!meetings[month]) meetings[month] = [0, 0, 0, 0];
        meetings[month][week] += duration;
    }

    function updateChart() {
        console.log('Updating chart with data:', meetings);

        const chartData = {
            labels: Object.keys(meetings),
            datasets: [{
                label: 'Hours in Meetings',
                data: Object.keys(meetings).map(month => meetings[month].reduce((a, b) => a + b, 0)),
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        };

        if (window.myChart && window.myChart.destroy) {
            window.myChart.destroy();
        }

        const ctx = document.getElementById('myChart').getContext('2d');
        console.log('Canvas context:', ctx);

        window.myChart = new Chart(ctx, {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(1); // Display up to 1 decimal place on the y-axis
                            }
                        }
                    }
                },
                onClick: (e, item) => {
                    if (item.length) {
                        const month = chartData.labels[item[0].index];
                        showWeekChart(month);
                    }
                }
            }
        });
    }

    function showWeekChart(month) {
        // Placeholder for weekly chart implementation
    }

    updateChart();
});
