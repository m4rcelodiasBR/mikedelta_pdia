(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.mikedeltaPdia = {
    attach: function (context, settings) {
      const elements = once('mikedelta-pdia-init', '#pdia-grid', context);

      elements.forEach(function (container) {
        const dados = settings.mikedeltaPdia || {};
        const arquivos = dados.arquivos || {};
        const licencas = dados.licencas || {};
        const nacionais = dados.nacionais || {};
        const regionais = dados.regionais || {};
        const especificos = dados.especificos || {};
        const iconePdf = dados.icone_pdf || '';

        let currentDate = new Date();

        const titleYear = document.querySelector('#pdia-year-title');
        const titleMonth = document.querySelector('#pdia-month-title');
        
        function renderCalendar() {
          container.innerHTML = '';
          const year = currentDate.getFullYear();
          const month = currentDate.getMonth();
          
          const monthNames = ["JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO"];
          if (titleYear) titleYear.textContent = year;
          if (titleMonth) titleMonth.textContent = monthNames[month];

          const firstDay = new Date(year, month, 1).getDay();
          const daysInMonth = new Date(year, month + 1, 0).getDate();

          for (let i = 0; i < firstDay; i++) {
            container.appendChild(document.createElement('div'));
          }

          for (let day = 1; day <= daysInMonth; day++) {
            const dayDiv = document.createElement('div');
            dayDiv.className = 'pdia-day';
            
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const monthDayStr = `${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const currentDayOfWeek = new Date(year, month, day).getDay();

            dayDiv.innerHTML = `<span>${day}</span>`;

            if (currentDayOfWeek === 0 || currentDayOfWeek === 6) {
              dayDiv.classList.add('pdia-weekend');
            }

            if (licencas[dateStr]) {
              dayDiv.classList.add('pdia-license');
              dayDiv.setAttribute('title', 'Licença MB');
              dayDiv.innerHTML += `<small class="pdia-tag">${licencas[dateStr]}</small>`;
            } else if (especificos[dateStr]) {
              dayDiv.classList.add('pdia-license');
              dayDiv.setAttribute('title', 'Feriado Específico');
              dayDiv.innerHTML += `<small class="pdia-tag">${especificos[dateStr]}</small>`;
            } else if (regionais[monthDayStr]) {
              dayDiv.classList.add('pdia-license');
              dayDiv.setAttribute('title', 'Feriado Regional');
              dayDiv.innerHTML += `<small class="pdia-tag">${regionais[monthDayStr]}</small>`;
            } else if (nacionais[dateStr]) {
              dayDiv.classList.add('pdia-license');
              dayDiv.setAttribute('title', 'Feriado Nacional');
              dayDiv.innerHTML += `<small class="pdia-tag">${nacionais[dateStr]}</small>`;
            }

            if (arquivos[dateStr]) {
              dayDiv.classList.add('has-pdf');
              dayDiv.style.cursor = 'pointer';
              const renderIcon = iconePdf ? `<img src="${iconePdf}" class="pdf-pdia-icon" alt="PDF">` : '📄';
              dayDiv.innerHTML += renderIcon;
              
              dayDiv.addEventListener('click', function() {
                window.open(arquivos[dateStr], '_blank');
              });
            }

            container.appendChild(dayDiv);
          }
        }

        document.querySelector('#pdia-prev-year')?.addEventListener('click', () => { currentDate.setFullYear(currentDate.getFullYear() - 1); renderCalendar(); });
        document.querySelector('#pdia-next-year')?.addEventListener('click', () => { currentDate.setFullYear(currentDate.getFullYear() + 1); renderCalendar(); });
        document.querySelector('#pdia-prev-month')?.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); });
        document.querySelector('#pdia-next-month')?.addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); });
        
        document.querySelector('#pdia-btn-hoje')?.addEventListener('click', () => { currentDate = new Date(); renderCalendar(); });
        
        document.querySelector('#pdia-btn-goto')?.addEventListener('click', () => {
          const gotoMonth = document.querySelector('#pdia-goto-month').value;
          const gotoYear = document.querySelector('#pdia-goto-year').value;
          if (gotoYear >= 2013 && gotoYear <= 2100) {
            currentDate.setFullYear(gotoYear);
            currentDate.setMonth(gotoMonth);
            renderCalendar();
          } else {
            alert('Por favor, insira um ano válido entre 2013 e 2100.');
          }
        });

        const inputYear = document.querySelector('#pdia-goto-year');
        if(inputYear) inputYear.value = currentDate.getFullYear();

        renderCalendar();
      });
    }
  };
})(Drupal, drupalSettings, once);