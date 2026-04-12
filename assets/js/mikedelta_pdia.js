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
        const fundoAtivo = dados.fundo_ativo ?? true;
        const opacidadeFundo = dados.fundo_opacidade ?? '0.88';
        const imagensFundo = dados.imagens_fundo || [];
        const dataDeHoje = new Date();
        const stringHoje = `${dataDeHoje.getFullYear()}-${String(dataDeHoje.getMonth() + 1).padStart(2, '0')}-${String(dataDeHoje.getDate()).padStart(2, '0')}`;
        let currentDate = new Date();
        const titleYear = document.querySelector('#pdia-year-title');
        const titleMonth = document.querySelector('#pdia-month-title');
        
        // Controle de botões (Limite 2013-2100)
        function updateControls(year, month) {
          const prevYearBtn = document.querySelector('#pdia-prev-year');
          const prevMonthBtn = document.querySelector('#pdia-prev-month');
          const nextYearBtn = document.querySelector('#pdia-next-year');
          const nextMonthBtn = document.querySelector('#pdia-next-month');

          if(prevYearBtn) prevYearBtn.disabled = (year <= 2013);
          if(prevMonthBtn) prevMonthBtn.disabled = (year <= 2013 && month === 0);
          if(nextYearBtn) nextYearBtn.disabled = (year >= 2100);
          if(nextMonthBtn) nextMonthBtn.disabled = (year >= 2100 && month === 11);
        }

        function renderCalendar() {
          container.innerHTML = '';
          const year = currentDate.getFullYear();
          const month = currentDate.getMonth();
          
          updateControls(year, month);

          const monthNames = ["JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO"];
          if (titleYear) titleYear.textContent = year;
          if (titleMonth) titleMonth.textContent = monthNames[month];

          const firstDay = new Date(year, month, 1).getDay();
          const daysInMonth = new Date(year, month + 1, 0).getDate();
          const prevMonthDays = new Date(year, month, 0).getDate(); // Total de dias do mês anterior

          // 1. Preenche slots vazios do mês anterior (Esmaecidos)
          for (let i = firstDay - 1; i >= 0; i--) {
            const prevDayDiv = document.createElement('div');
            prevDayDiv.className = 'pdia-day pdia-day-other-month';
            prevDayDiv.innerHTML = `<span class="dia-numero">${prevMonthDays - i}</span><div class="conteudo-celula"></div>`;
            container.appendChild(prevDayDiv);
          }

          // 2. Preenche os dias do mês atual
          for (let day = 1; day <= daysInMonth; day++) {
            const dayDiv = document.createElement('div');
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const monthDayStr = `${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const currentDayOfWeek = new Date(year, month, day).getDay();

            const classeAtual = (dateStr === stringHoje) ? ' dia-atual' : '';
            dayDiv.className = `pdia-day${classeAtual}`;
            dayDiv.innerHTML = `<span class="dia-numero">${day}</span><div class="conteudo-celula"></div>`;
            const conteudoDiv = dayDiv.querySelector('.conteudo-celula');

            if (currentDayOfWeek === 0 || currentDayOfWeek === 6) { dayDiv.classList.add('pdia-weekend'); }

            if (licencas[dateStr]) {
              dayDiv.classList.add('pdia-license'); dayDiv.setAttribute('title', 'Licença MB');
              conteudoDiv.innerHTML += `<small class="pdia-tag">${licencas[dateStr]}</small>`;
            } else if (especificos[dateStr]) {
              dayDiv.classList.add('pdia-license'); dayDiv.setAttribute('title', 'Feriado Específico');
              conteudoDiv.innerHTML += `<small class="pdia-tag">${especificos[dateStr]}</small>`;
            } else if (regionais[monthDayStr]) {
              dayDiv.classList.add('pdia-license'); dayDiv.setAttribute('title', 'Feriado Regional');
              conteudoDiv.innerHTML += `<small class="pdia-tag">${regionais[monthDayStr]}</small>`;
            } else if (nacionais[dateStr]) {
              dayDiv.classList.add('pdia-license'); dayDiv.setAttribute('title', 'Feriado Nacional');
              conteudoDiv.innerHTML += `<small class="pdia-tag">${nacionais[dateStr]}</small>`;
            }

            if (arquivos[dateStr]) {
              dayDiv.classList.add('has-pdf');
              const renderIconText = iconePdf ? `<img src="${iconePdf}" class="pdf-pdia-icon" alt="PDF"><span class="pdia-pdf-text">Plano do Dia</span>` : '📄';
              conteudoDiv.innerHTML += renderIconText;
              dayDiv.addEventListener('click', function() { window.open(arquivos[dateStr], '_blank'); });
            }
            container.appendChild(dayDiv);
          }

          // 3. Preenche os slots restantes do próximo mês (Esmaecidos)
          const totalCellsRendered = firstDay + daysInMonth;
          const nextDaysNeeded = totalCellsRendered % 7 === 0 ? 0 : 7 - (totalCellsRendered % 7);
          for(let i = 1; i <= nextDaysNeeded; i++) {
            const nextDayDiv = document.createElement('div');
            nextDayDiv.className = 'pdia-day pdia-day-other-month';
            nextDayDiv.innerHTML = `<span class="dia-numero">${i}</span><div class="conteudo-celula"></div>`;
            container.appendChild(nextDayDiv);
          }
        }

        // Listeners de navegação
        document.querySelector('#pdia-prev-year')?.addEventListener('click', () => { if(currentDate.getFullYear() > 2013) { currentDate.setFullYear(currentDate.getFullYear() - 1); renderCalendar(); }});
        document.querySelector('#pdia-next-year')?.addEventListener('click', () => { if(currentDate.getFullYear() < 2100) { currentDate.setFullYear(currentDate.getFullYear() + 1); renderCalendar(); }});
        document.querySelector('#pdia-prev-month')?.addEventListener('click', () => { if(currentDate.getFullYear() > 2013 || currentDate.getMonth() > 0) { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); }});
        document.querySelector('#pdia-next-month')?.addEventListener('click', () => { if(currentDate.getFullYear() < 2100 || currentDate.getMonth() < 11) { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); }});
        
        document.querySelector('#pdia-btn-hoje')?.addEventListener('click', () => { currentDate = new Date(); renderCalendar(); });
        document.querySelector('#pdia-btn-goto')?.addEventListener('click', () => {
          const gotoMonth = document.querySelector('#pdia-goto-month').value;
          const gotoYear = document.querySelector('#pdia-goto-year').value;
          if (gotoYear >= 2013 && gotoYear <= 2100) {
            currentDate.setFullYear(gotoYear); currentDate.setMonth(gotoMonth); renderCalendar();
          } else { alert('Por favor, insira um ano válido entre 2013 e 2100.'); }
        });

        const inputYear = document.querySelector('#pdia-goto-year');
        if(inputYear) inputYear.value = currentDate.getFullYear();

        renderCalendar();

        // Aplicador de Imagem de Fundo Inteligente
        const pdiaContainer = document.querySelector('.pdia-container');
        if (pdiaContainer && !pdiaContainer.dataset.bgSet && fundoAtivo && imagensFundo.length > 0) {
          const imagemSorteada = imagensFundo[Math.floor(Math.random() * imagensFundo.length)];
          pdiaContainer.style.backgroundImage = `linear-gradient(rgba(255, 255, 255, ${opacidadeFundo}), rgba(255, 255, 255, ${opacidadeFundo})), url('${imagemSorteada}')`;
          pdiaContainer.style.backgroundSize = 'cover';
          pdiaContainer.style.backgroundPosition = 'center';
          pdiaContainer.style.backgroundRepeat = 'no-repeat';
          pdiaContainer.style.backgroundColor = 'transparent';
          pdiaContainer.dataset.bgSet = 'true'; 
        } else if (pdiaContainer && !fundoAtivo) {
          pdiaContainer.style.background = '#c1c1c1';
        }
      });
    }
  };
})(Drupal, drupalSettings, once);