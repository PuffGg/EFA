const header = document.querySelector('.calendar h3');
const dates = document.querySelector('.dates');
const navs = document.querySelectorAll('#prev, #next');
const inputNote = document.getElementById('input-note');
const saveNoteButton = document.getElementById('save-note');
const weekendOptions = document.getElementById('weekend-options');
const markPresentButton = document.getElementById('mark-present-button');
const markPresentRadio = document.getElementById('mark-present');

const months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December"
];

let date = new Date();
let month = date.getMonth();
let year = date.getFullYear();
let selectedDate = null;

function renderCalendar() {
    const start = new Date(year, month, 1).getDay();
    const endDate = new Date(year, month + 1, 0).getDate();
    const end = new Date(year, month, endDate).getDay();
    const endDatePrev = new Date(year, month, 0).getDate();

    let datesHtml = "";

    // Previous month's dates
    for (let i = start; i > 0; i--) {
        datesHtml += `<li class="inactive">${endDatePrev - i + 1}</li>`;
    }

    // Current month's dates
    for (let i = 1; i <= endDate; i++) {
        let className = (
            i === date.getDate() &&
            month === new Date().getMonth() &&
            year === new Date().getFullYear()
        ) ? 'class="today"' : "";
        datesHtml += `<li ${className} data-date="${i}">${i}</li>`;
    }

    // Next month's dates
    for (let i = end; i < 6; i++) {
        datesHtml += `<li class="inactive">${i - end + 1}</li>`;
    }

    dates.innerHTML = datesHtml;
    header.textContent = `${months[month]} ${year}`;

    // Add click event listeners to dates
    const dateElements = document.querySelectorAll('.dates li:not(.inactive)');
    dateElements.forEach(dateElement => {
        dateElement.addEventListener('click', e => {
            selectedDate = e.target.getAttribute('data-date');
            inputNote.value = ''; // Clear input field
            inputNote.focus();

            // Reset the radio button
            markPresentRadio.checked = false;

            // Show the weekend options if the selected date is a Saturday or Sunday
            const dayOfWeek = new Date(year, month, selectedDate).getDay();
            if (dayOfWeek === 0 || dayOfWeek === 6) {
                weekendOptions.style.display = 'block';
            } else {
                weekendOptions.style.display = 'none';
            }
        });
    });
}

navs.forEach(nav => {
    nav.addEventListener('click', e => {
        const btnId = e.target.id;

        if (btnId === 'prev' && month === 0) {
            year--;
            month = 11;
        } else if (btnId === 'next' && month === 11) {
            year++;
            month = 0;
        } else {
            month = (btnId === 'next') ? month + 1 : month - 1;
        }

        renderCalendar();
    });
});

saveNoteButton.addEventListener('click', () => {
    const note = inputNote.value;
    if (note && selectedDate) {
        // Example: Send note to server (adjust the URL and payload as needed)
        fetch('process_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                date: `${year}-${month + 1}-${selectedDate}`,
                note: note
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
        })
        .catch(error => {
            console.error('Error saving note:', error);
        });
    } else {
        alert('Please select a date and write a note.');
    }
});

markPresentButton.addEventListener('click', () => {
    if (selectedDate) {
        // Example: Send mark present request to server (adjust the URL and payload as needed)
        fetch('process_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                date: `${year}-${month + 1}-${selectedDate}`,
                weekend: 'present'
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.message);
        })
        .catch(error => {
            console.error('Error marking present:', error);
        });
    } else {
        alert('Please select a date.');
    }
});

renderCalendar();
