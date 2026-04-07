(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.mikedeltaPdiaAdmin = {
    attach: function (context, settings) {
      
      const adminElements = once('pdia-admin-init', '#pdia-modal-licencas', context);

      adminElements.forEach(function (modal) {
        const btnOpen = document.getElementById('btn-open-modal-licencas');
        const btnClose = document.getElementById('btn-close-modal');
        const form = document.getElementById('form-licenca-admin');
        const radioOutros = document.getElementById('radio-outros');
        const inputOutros = document.getElementById('admin-licenca-outro');
        const listaContainer = document.getElementById('lista-licencas-container');
        const listaUl = document.getElementById('lista-licencas-mes');

        const licencas = settings.mikedeltaPdia.licencas || {};

        document.querySelectorAll('input[name="tipo_evento"]').forEach(radio => {
          radio.addEventListener('change', (e) => {
            if (e.target.value === 'outros') {
              inputOutros.disabled = false;
              inputOutros.required = true;
              inputOutros.focus();
            } else {
              inputOutros.disabled = true;
              inputOutros.required = false;
              inputOutros.value = '';
            }
          });
        });

        // Abrir Modal e popular lista do mês
        btnOpen.addEventListener('click', () => {
          listaUl.innerHTML = '';
          
          // Lendo os novos elementos de Ano e Mês separadamente
          const mesTexto = document.getElementById('pdia-month-title').textContent;
          const anoTexto = document.getElementById('pdia-year-title').textContent;

          const monthNames = ["JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO"];
          const mesIndex = monthNames.indexOf(mesTexto) + 1;
          const prefixoData = `${anoTexto}-${String(mesIndex).padStart(2, '0')}`;

          let temLicenca = false;

          for (const [data, nome] of Object.entries(licencas)) {
            if (data.startsWith(prefixoData)) {
              temLicenca = true;
              const [y, m, d] = data.split('-');
              const li = document.createElement('li');
              li.innerHTML = `
                <span><strong>${d}/${m}/${y}</strong> - ${nome}</span>
                <button type="button" class="btn-excluir" data-date="${data}">Excluir</button>
              `;
              listaUl.appendChild(li);
            }
          }

          listaContainer.style.display = temLicenca ? 'block' : 'none';
          modal.style.display = 'flex';
        });

        // Fechar Modal
        btnClose.addEventListener('click', () => modal.style.display = 'none');

        // Excluir Licença
        listaUl.addEventListener('click', (e) => {
          if (e.target.classList.contains('btn-excluir')) {
            const dataExcluir = e.target.getAttribute('data-date');
            if (confirm(`Excluir o evento do dia ${dataExcluir}?`)) {
              fetch('/admin/api/md-pdia/apagar-licenca', {
                method: 'POST',
                body: JSON.stringify({ date: dataExcluir }),
                headers: { 'Content-Type': 'application/json' }
              }).then(res => res.json()).then(data => {
                if(data.status === 'success') location.reload();
              });
            }
          }
        });

        // Salvar Nova Licença
        form.addEventListener('submit', (e) => {
          e.preventDefault();
          const dataSel = document.getElementById('admin-licenca-data').value;
          let tipoSel = document.querySelector('input[name="tipo_evento"]:checked').value;
          
          if (tipoSel === 'outros') {
            tipoSel = inputOutros.value.trim();
          }

          fetch('/admin/api/md-pdia/salvar-licenca', {
            method: 'POST',
            body: JSON.stringify({ date: dataSel, name: tipoSel }),
            headers: { 'Content-Type': 'application/json' }
          }).then(res => res.json()).then(data => {
            if(data.status === 'success') {
              location.reload(); 
            } else {
              alert(data.message);
            }
          });
        });

      });
    }
  };
})(Drupal, drupalSettings, once);