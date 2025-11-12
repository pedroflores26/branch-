// script.js - Dashboard (empresa)
/* global Chart */

(() => {
  // ==========================
  // Helpers
  // ==========================
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const fmtPrice = (v) => {
    const num = parseFloat(String(v).replace(',', '.')) || 0;
    return num.toFixed(2).replace('.', ',');
  };

  // ==========================
  // Endpoints (ajuste se necessário)
  // ==========================
  const ENDPOINT_LIST = 'listarProdutos.php';
  const ENDPOINT_PROCESS = 'processaProduto.php';
  const ENDPOINT_DADOS = 'dadosDashboard.php';
  const ENDPOINT_LOGOUT = 'logout.php';

  // ==========================
  // Elements
  // ==========================
  const el = {
    // cards
    cardTotalProdutos: $('#cardTotalProdutos'),
    cardTotalAnuncios: $('#cardTotalAnuncios'),
    totalProdutosSmall: $('#totalProdutos'), // no seu HTML já existe esse id
    // tabela
    tabelaBody: $('#lista-produtos'),
    searchInput: $('#search'),
    // form
    formProduto: $('#formProduto'),
    fotoInput: $('#foto'),
    fotoPreview: $('#fotoPreview'),
    // nav / sections
    linksSidebar: $$('.sidebar nav a'),
    secDashboard: $('#sec-dashboard'),
    secPerfil: $('#sec-perfil'),
    secAdicionar: $('#sec-adicionar'),
    // logout
    btnSairPerfil: $('#btnSair'),
    btnSidebarSair: $('#btnSidebarSair'),
    // header
    headerEmpresaNome: $('#headerEmpresaNome')
  };

  // Safe defaults
  for (const k in el) if (!el[k]) el[k] = null;

  // ==========================
  // Listar produtos
  // ==========================
 async function listarProdutos() {
  try {
    const resp = await fetch("listarProdutos.php", { credentials: "include" });
    const json = await resp.json();

    if (json.status !== "ok") {
      console.error("Erro ao listar produtos:", json.message || "Resposta inválida do servidor.");
      return;
    }

    const lista = document.getElementById("lista-produtos");
    lista.innerHTML = "";

    json.produtos.forEach(produto => {
      lista.innerHTML += `
        <tr>
          <td><img src="${produto.foto_principal}" width="60" style="border-radius:6px; object-fit: cover;"></td>
          <td>${produto.nome}</td>
          <td>R$ ${produto.preco}</td>
        </tr>
      `;
    });

    document.getElementById("totalProdutos").textContent = json.produtos.length;
  } catch (erro) {
    console.error("Erro ao carregar produtos:", erro);
  }
}


  // small helper to avoid XSS in inserted HTML
  function escapeHtml(s) {
    if (s == null) return '';
    return String(s).replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[m]));
  }

  // ==========================
  // Carregar cards do dashboard
  // ==========================
  async function carregarDashboard() {
    try {
      const resp = await fetch(ENDPOINT_DADOS, { cache: 'no-store' });
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      const json = await resp.json();
      if (json.status === 'ok') {
        if (el.cardTotalProdutos) el.cardTotalProdutos.textContent = json.produtos ?? json.total_produtos ?? '0';
        if (el.cardTotalAnuncios) el.cardTotalAnuncios.textContent = json.anuncios ?? '0';
      } else {
        console.warn('dadosDashboard retornou erro', json);
      }
    } catch (err) {
      console.error('carregarDashboard:', err);
    }
  }

  // ==========================
  // Cadastro de produto (AJAX)
  // ==========================
  async function cadastrarProduto(event) {
    event.preventDefault();
    if (!el.formProduto) return;
    const fd = new FormData(el.formProduto);

    try {
      const resp = await fetch(ENDPOINT_PROCESS, { method: 'POST', body: fd });
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      const json = await resp.json();
      if (json.status === 'ok') {
        alert(json.message || 'Produto cadastrado com sucesso!');
        el.formProduto.reset();
        if (el.fotoPreview) el.fotoPreview.innerHTML = '<span>+</span>';
        await listarProdutos();
        await carregarDashboard();
      } else {
        alert('Erro ao cadastrar: ' + (json.mensagem_erro || json.message || 'Verifique os dados.'));
        console.error('processaProduto erro:', json);
      }
    } catch (err) {
      console.error('cadastrarProduto:', err);
      alert('Erro ao conectar com o servidor ao cadastrar produto.');
    }
  }

  // ==========================
  // Preview de foto
  // ==========================
  function setupPreview() {
    if (!el.fotoInput || !el.fotoPreview) return;
    el.fotoInput.addEventListener('change', e => {
      const f = e.target.files && e.target.files[0];
      if (!f) {
        el.fotoPreview.innerHTML = '<span>+</span>';
        return;
      }
      const reader = new FileReader();
      reader.onload = ev => {
        el.fotoPreview.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;border-radius:8px;">`;
      };
      reader.readAsDataURL(f);
    });
  }

  // ==========================
  // Busca na tabela
  // ==========================
  function setupSearch() {
    if (!el.searchInput) return;
    el.searchInput.addEventListener('input', () => {
      const q = el.searchInput.value.trim().toLowerCase();
      $$('#lista-produtos tr').forEach(tr => {
        const nome = (tr.children[1]?.textContent || '').toLowerCase();
        tr.style.display = nome.includes(q) ? '' : 'none';
      });
    });
  }

  // ==========================
  // Navegação entre seções (sidebar)
  // ==========================
  function setupSidebarNavigation() {
    if (!el.linksSidebar || el.linksSidebar.length === 0) return;
    el.linksSidebar.forEach((link, idx) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        // remove active
        el.linksSidebar.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        // hide all
        if (el.secDashboard) el.secDashboard.style.display = 'none';
        if (el.secPerfil) el.secPerfil.style.display = 'none';
        if (el.secAdicionar) el.secAdicionar.style.display = 'none';

        if (idx === 0 && el.secDashboard) el.secDashboard.style.display = 'block';
        if (idx === 1 && el.secPerfil) el.secPerfil.style.display = 'block';
        if (idx === 2 && el.secAdicionar) el.secAdicionar.style.display = 'block';
      });
    });
  }

  // ==========================
  // Logout
  // ==========================
  async function doLogout() {
    try {
      const resp = await fetch(ENDPOINT_LOGOUT, { method: 'POST' });
      // logout.php retorna JSON {status:'ok'} na versão que sugeri
      if (resp.ok) {
        // remover localStorage (caso use)
        try { localStorage.removeItem('usuarioLogado'); } catch(e){}
        // redireciona para página de login/landing
        window.location.href = 'indexLogin.html' || 'login.php' || '/';
      } else {
        // fallback: redireciona mesmo
        window.location.href = 'indexLogin.html' || 'login.php' || '/';
      }
    } catch (err) {
      console.error('Erro no logout:', err);
      window.location.href = 'indexLogin.html' || 'login.php' || '/';
    }
  }

  // ==========================
  // Perfil dinâmico (header)
  // ==========================
  function preencherPerfilHeader() {
    try {
      const usuario = JSON.parse(localStorage.getItem('usuarioLogado') || 'null');
      if (!usuario) return;
      const nomeEmpresa = usuario.nome_razao_social || usuario.nome_empresa || usuario.nome || '';
      if (el.headerEmpresaNome) el.headerEmpresaNome.textContent = nomeEmpresa;
      if ($('#perfilNomeEmpresa')) $('#perfilNomeEmpresa').textContent = nomeEmpresa;
      if ($('#perfilCNPJ')) $('#perfilCNPJ').textContent = 'CNPJ: ' + (usuario.cnpj || '---');
      if ($('#perfilEmail')) $('#perfilEmail').textContent = usuario.email || '---';
      if ($('#perfilTelefone')) $('#perfilTelefone').textContent = usuario.telefone || '---';
      $$('#perfilCategoria').forEach(elm => elm.textContent = usuario.categoria || 'Categoria...');
      $$('#perfilEndereco').forEach(elm => elm.textContent = usuario.endereco || 'Endereço...');
    } catch (err) {
      // não crítico
      console.warn('preencherPerfilHeader:', err);
    }
  }

  // ==========================
  // Inicialização
  // ==========================
  function init() {
    // eventos
    if (el.formProduto) el.formProduto.addEventListener('submit', cadastrarProduto);
    setupPreview();
    setupSearch();
    setupSidebarNavigation();
    preencherPerfilHeader();

    // logout
    if (el.btnSairPerfil) el.btnSairPerfil.addEventListener('click', doLogout);
    if (el.btnSidebarSair) el.btnSidebarSair.addEventListener('click', doLogout);

    // inicial load
    listarProdutos();
    carregarDashboard();

    // gráfico (opcional) - só cria se existir canvas
    const graf = $('#graficoVendas');
    if (graf && typeof Chart !== 'undefined') {
      new Chart(graf, {
        type: 'bar',
        data: {
          labels: ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'],
          datasets: [
            { label: 'Vendas', data: [300,400,350,500,480,600,580,540,570,610,620,630] },
            { label: 'Itens', data: [200,250,240,300,280,350,340,320,330,360,370,380] }
          ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
      });
    }
  }

  

  // run
  document.addEventListener('DOMContentLoaded', init);
})();
