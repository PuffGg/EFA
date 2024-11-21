document.getElementById('addMeetingButton').addEventListener('click', function() {
    const month = document.getElementById('month').value;
    const week = document.getElementById('week').value;
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'meetings.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            console.log(xhr.responseText); // Debugging: Log the response to console
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        updateChart(response.data, 'first');
                        updateYearlyChart(); // Update the second chart
                    } else {
                        showResponseMessage(response.message, 'error');
                    }
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                }
            } else {
                showResponseMessage('Request failed. Please check the server.', 'error');
            }
        }
    };

    xhr.send(`month=${month}&week=${week}&startTime=${startTime}&endTime=${endTime}`);
});

// Function to update charts
function updateChart(data, type) {
    const ctx = document.getElementById('myChart').getContext('2d');
    const labels = data.map(item => item.meeting_date);
    const durations = data.map(item => item.total_duration);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Meeting Duration (Hours)',
                data: durations,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Hours' }
                }
            }
        }
    });
}

// Function to update yearly chart
function updateYearlyChart() {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'meetings.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            console.log(xhr.responseText); // Debugging: Log the response to console
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.status === 'success') {
                        const data = response.data;
                        const ctx = document.getElementById('yearlyChart').getContext('2d');
                        const labels = data.map(item => item.month);
                        const durations = data.map(item => item.total_duration);

                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Monthly Total Duration (Hours)',
                                    data: durations,
                                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                                    borderColor: 'rgba(153, 102, 255, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: { display: true, text: 'Hours' }
                                    }
                                }
                            }
                        });
                    } else {
                        console.error('Error: ' + response.message);
                    }
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                }
            } else {
                console.error('Request failed. Please check the server.');
            }
        }
    };

    xhr.send('yearly=true');
}

// Function to show success or error messages
function showResponseMessage(message, type) {
    const responseMessage = document.getElementById('responseMessage');
    responseMessage.textContent = message;
    responseMessage.className = `message ${type}`;
}

document.getElementById('showGraphsButton').addEventListener('click', function() {
    // Fetch the necessary data for both graphs (weekly and yearly)
    
    // First, fetch the monthly data for the current year
    const xhrMonthly = new XMLHttpRequest();
    xhrMonthly.open('POST', 'meetings.php', true);
    xhrMonthly.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhrMonthly.onreadystatechange = function() {
        if (xhrMonthly.readyState === 4) {
            if (xhrMonthly.status === 200) {
                try {
                    const response = JSON.parse(xhrMonthly.responseText);
                    if (response.status === 'success') {
                        updateChart(response.data, 'first'); // Update the first chart (monthly data)
                    } else {
                        showResponseMessage(response.message, 'error');
                    }
                } catch (e) {
                    console.error('Failed to parse monthly data JSON:', e);
                }
            } else {
                showResponseMessage('Request failed for monthly data. Please check the server.', 'error');
            }
        }
    };
    xhrMonthly.send('yearly=true'); // Send the request to get yearly data

    // Fetch the yearly data (e.g., for a summary of meeting durations over the year)
    const xhrYearly = new XMLHttpRequest();
    xhrYearly.open('POST', 'meetings.php', true);
    xhrYearly.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhrYearly.onreadystatechange = function() {
        if (xhrYearly.readyState === 4) {
            if (xhrYearly.status === 200) {
                try {
                    const response = JSON.parse(xhrYearly.responseText);
                    if (response.status === 'success') {
                        updateYearlyChart(response.data); // Update the yearly chart
                    } else {
                        showResponseMessage(response.message, 'error');
                    }
                } catch (e) {
                    console.error('Failed to parse yearly data JSON:', e);
                }
            } else {
                showResponseMessage('Request failed for yearly data. Please check the server.', 'error');
            }
        }
    };
    xhrYearly.send('yearly=true'); // Send the request to get yearly data
});

// Function to update the first chart (Monthly/Weekly Data)
function updateChart(data, type) {
    const ctx = document.getElementById('myChart').getContext('2d');
    const labels = data.map(item => item.month);  // Assuming `data` contains monthly data with `month` key
    const durations = data.map(item => item.total_duration);  // Assuming `total_duration` in hours

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Meeting Duration (Hours)',
                data: durations,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Total Duration (Hours)'
                    }
                }
            }
        }
    });
}

// Function to update the second chart (Yearly Data)
function updateYearlyChart(data) {
    const ctx = document.getElementById('yearlyChart').getContext('2d');
    const labels = data.map(item => item.month);  // Assuming `data` contains monthly data for the year
    const durations = data.map(item => item.total_duration);  // Assuming `total_duration` in hours

    new Chart(ctx, {
        type: 'line',  // Using line chart for yearly data
        data: {
            labels: labels,
            datasets: [{
                label: 'Meeting Duration (Hours)',
                data: durations,
                fill: false,
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Total Duration (Hours)'
                    }
                }
            }
        }
    });
}



   // Function to check if today is Friday, Saturday, or the last Sunday of the month
   function isValidMeetingDay() {
    const today = new Date();
    const dayOfWeek = today.getDay();  // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
    const currentDate = today.getDate();

    // Check if today is Friday (5), Saturday (6), or last Sunday of the month
    if (dayOfWeek === 3 || dayOfWeek === 6 || isLastSundayOfMonth(today)) {
        return true; // Valid day for meeting
    }
    return false; // Invalid day for meeting
}

// Function to check if today is the last Sunday of the month
function isLastSundayOfMonth(date) {
    const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    const lastSunday = lastDay.getDate() - lastDay.getDay(); // Get the date of the last Sunday
    return date.getDate() === lastSunday;
}

// Function to restrict the time selection between 4:00 PM and 4:10 PM
function restrictTime() {
    const startTimeField = document.getElementById('startTime');
    const endTimeField = document.getElementById('endTime');

    const timeFormat = "16:00";  // 4:00 PM
    const endTime = "18:50";     // 4:10 PM

    startTimeField.setAttribute('min', timeFormat);
    startTimeField.setAttribute('max', endTime);
    endTimeField.setAttribute('min', timeFormat);
    endTimeField.setAttribute('max', endTime);
}


// Timer countdown function
function startTimer() {
    const timerElement = document.getElementById('timer');
    const endTime = new Date();
    endTime.setHours(18, 50, 0); // Set the end time to 4:10 PM today

    function updateTimer() {
        const currentTime = new Date();
        const timeRemaining = endTime - currentTime;
        if (timeRemaining <= 0) {
            timerElement.textContent = "Time's up! The fields are no longer fillable.";
            document.getElementById('startTime').disabled = true;
            document.getElementById('endTime').disabled = true;
            clearInterval(timerInterval);
        } else {
            const minutesRemaining = Math.floor(timeRemaining / 60000); // Convert milliseconds to minutes
            const secondsRemaining = Math.floor((timeRemaining % 60000) / 1000); // Get remaining seconds
            timerElement.textContent = `${minutesRemaining} minutes and ${secondsRemaining} seconds remaining`;
        }
    }

    updateTimer();
    const timerInterval = setInterval(updateTimer, 1000); // Update the timer every second
}

// Set the current week in the HTML
function getCurrentWeekOfMonth() {
    const today = new Date();
    const dayOfMonth = today.getDate();
    return Math.ceil(dayOfMonth / 7);
}

const currentWeek = getCurrentWeekOfMonth();
document.getElementById("current-week").textContent = "We are in Week " + currentWeek;

// Get the current day and display it in the HTML
function getCurrentDay() {
    const today = new Date();
    const daysOfWeek = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    const dayOfWeek = today.getDay(); // Get the day of the week (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
    const currentDate = today.toISOString().split('T')[0]; // Format the date as YYYY-MM-DD
    return `${daysOfWeek[dayOfWeek]}, ${currentDate}`; // Return formatted string like "Monday, 2024-11-14"
}


// Display the current day in the HTML
document.getElementById("current-day").textContent = getCurrentDay();

// Disable time inputs if today is not a valid meeting day
if (!isValidMeetingDay()) {
    document.getElementById('startTime').disabled = true;
    document.getElementById('endTime').disabled = true;

} else {
    // Restrict time selection to 4:00 PM to 4:10 PM
    restrictTime();
    startTimer(); // Start the countdown timer
}


function handleFormSubmission(event) {
    event.preventDefault();
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'your_php_script.php', true);
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                const responseMessageDiv = document.getElementById('responseMessage');
                
                if (response.status === 'success') {
                    responseMessageDiv.textContent = response.message;
                    responseMessageDiv.style.color = 'green';  // Success style
                } else {
                    responseMessageDiv.textContent = response.message;
                    responseMessageDiv.style.color = 'red';  // Error style
                }
            } catch (e) {
                console.error('Failed to parse JSON:', e);
            }
        }
    };
    
    xhr.send(new FormData(document.getElementById('yourForm')));
}
