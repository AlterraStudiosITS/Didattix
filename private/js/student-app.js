document.addEventListener('DOMContentLoaded', () => {
    const sideMenu = document.querySelector("aside");
    const profileBtn = document.querySelector("#profile-btn");
    const themeToggler = document.querySelector(".theme-toggler");
    const nextDay = document.getElementById('nextDay');
    const prevDay = document.getElementById('prevDay');

    profileBtn.onclick = function(){
        sideMenu.classList.toggle('active');
    }

    window.onscroll = () => {
        sideMenu.classList.remove('active');
        if(window.scrollY > 0) {
            document.querySelector('header').classList.add('active');
        }else{
            document.querySelector('header').classList.remove('active');
        }
    }

    const applySavedTheme = () => {
        const isDarkMode = localStorage.getItem('dark-theme') === 'true';

        const flatpickrLink = document.getElementById('flatpickr-theme');
        if(flatpickrLink){
            flatpickrLink.href = isDarkMode
                ? "https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css"
                : "https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css";
        }

        if(isDarkMode){
            document.body.classList.add('dark-theme');
            themeToggler.querySelector('span:nth-child(1)').classList.add('active');
            themeToggler.querySelector('span:nth-child(2)').classList.remove('active');
        }else{
            document.body.classList.remove('dark-theme');
            themeToggler.querySelector('span:nth-child(1)').classList.remove('active');
            themeToggler.querySelector('span:nth-child(2)').classList.add('active');
        }
    }

    applySavedTheme();

    themeToggler.onclick = function(){
        document.body.classList.toggle('dark-theme');
        
        themeToggler.querySelector('span:nth-child(1)').classList.toggle('active');
        themeToggler.querySelector('span:nth-child(2)').classList.toggle('active');
        
        const newTheme = document.body.classList.contains('dark-theme');
        localStorage.setItem('dark-theme', newTheme);

        const flatpickrLink = document.getElementById('flatpickr-theme');
        if(flatpickrLink){
            flatpickrLink.href = newTheme
                ? "https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css"
                : "https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css";
        }
    };

    function loadFlatpickrLocale(locale){
        return new Promise((resolve, reject) => {
            if(locale === 'en'){
                resolve();
                return;
            }
            const script = document.createElement('script');
            script.src = `https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/${locale}.js`;
            script.onload = () => resolve();
            script.onerror = () => reject(`Failed to load locale ${locale}`);
            document.head.appendChild(script);
        });
    }

    loadFlatpickrLocale(CONFIG.calendar.locale).then(() => {
        const defaultDate = document.getElementById("selected_date").value;

        function parseDMY(dateStr){
            const parts = dateStr.split("-");
            return new Date(parts[2], parts[1] - 1, parts[0]);
        }

        const schoolStartDate = parseDMY(CONFIG.calendar.schoolStart);
        const schoolEndDate = parseDMY(CONFIG.calendar.schoolEnd);
        const today = new Date();

        const maxDate = today < schoolEndDate ? today : schoolEndDate;

        flatpickr("#selected_date", {
            locale: CONFIG.calendar.locale,
            dateFormat: CONFIG.calendar.dateFormat,
            minDate: CONFIG.calendar.schoolStart,
            maxDate: maxDate,
            defaultDate: defaultDate,
            onChange: function(selectedDates, dateStr){
                document.getElementById("selected_date").value = dateStr;
                //loadPresences();
            }
        });
    });

    let setData = (day) => {
        document.querySelector('table tbody').innerHTML = '';
        let daylist = ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato"];
        document.querySelector('.timetable div h2').innerHTML = daylist[day];
        
        let daySchedule = [];
        switch(day){
            case 0: daySchedule = Sunday; break;
            case 1: daySchedule = Monday; break;
            case 2: daySchedule = Tuesday; break;
            case 3: daySchedule = Wednesday; break;
            case 4: daySchedule = Thursday; break;
            case 5: daySchedule = Friday; break;
            case 6: daySchedule = Saturday; break;
        }

        daySchedule.forEach(sub => {
            const tr = document.createElement('tr');
            const trContent = `
                <td>${sub.time}</td>
                <td>${sub.roomNumber}</td>
                <td>${sub.subject}</td>
                <td>${sub.type}</td>
            `;
            tr.innerHTML = trContent;
            document.querySelector('table tbody').appendChild(tr);
        });
    }

    let now = new Date();
    let today = now.getDay();
    let day = today;

    function timeTableAll(){
        document.getElementById('timetable').classList.toggle('active');
        setData(today);
        document.querySelector('.timetable div h2').innerHTML = "Orario giornaliero";
    }

    nextDay.onclick = function(){
        day <= 5 ? day++ : day = 0;
        setData(day);
    }

    prevDay.onclick = function(){
        day >= 1 ? day-- : day = 6;
        setData(day);
    }

    setData(day);  
    document.querySelector('.timetable div h2').innerHTML = "Orario giornaliero";

    /* ===== SEARCH ICONS ===== */
    const searchInput = document.getElementById('searchUser');
    const userList = document.getElementById('userList');

    searchInput.addEventListener('input', () => {
        const filter = searchInput.value.toLowerCase();
        const utentiDiv = userList.querySelectorAll('div');

        utentiDiv.forEach(div => {
            const nome = div.querySelector('h3').textContent.toLowerCase();
            if(nome.includes(filter)) div.style.display = '';
            else div.style.display = 'none';
        });
    });

    const searchClass = document.getElementById('searchClass');
    const classList = document.getElementById('classList');

    searchClass.addEventListener('input', () => {
        const filter = searchClass.value.toLowerCase();
        const classDivs = classList.querySelectorAll('div');

        classDivs.forEach(div => {
            const nome = div.querySelector('h3').textContent.toLowerCase();
            div.style.display = nome.includes(filter) ? '' : 'none';
        });
    });
});
