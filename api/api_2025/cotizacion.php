<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catálogo de Productos</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root {
  --primary: #2563eb; --primary-dark: #1d4ed8;
  --accent: #0ea5e9; --danger: #ef4444; --success: #22c55e; --success-dark: #16a34a;
  --surface: #ffffff; --bg: #f8fafc; --border: #e2e8f0;
  --radius: 10px; --radius-sm: 6px;
  --shadow: 0 4px 12px rgba(0,0,0,0.08);
  --text: #1e293b; --text-light: #64748b;
}
* { box-sizing: border-box; margin:0; padding:0; }
body { 
  font-family: 'Inter', sans-serif; 
  background: var(--surface);
  color: var(--text); 
  line-height: 1.5;
}
.container { 
  max-width: 100%; 
  margin: auto; 
  padding: 1.25rem; 
  padding-bottom: 100px;
}

.catalogo-header {
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  padding: 1rem 1.25rem;
  position: sticky;
  top: 0;
  z-index: 100;
}
.busqueda-bar { 
  display: flex; 
  gap: 0.75rem; 
  flex-wrap: wrap; 
  align-items: flex-end; 
}
.campo { 
  display: flex; 
  flex-direction: column; 
  gap: 0.25rem; 
  min-width: 200px; 
  flex: 1 1 auto;
  position: relative;
}
label { 
  font-size: 0.8rem; 
  font-weight: 600; 
  color: var(--text-light); 
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
input, select { 
  border: 1.5px solid var(--border); 
  border-radius: var(--radius-sm); 
  padding: 0.5rem 0.75rem; 
  font-size: 0.9rem; 
  background: #fff; 
  transition: border-color 0.2s;
}
input:focus, select:focus { 
  outline: none; 
  border-color: var(--accent); 
}

/* Autocompletado */
.autocomplete-suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid var(--border);
  border-top: none;
  border-radius: 0 0 var(--radius-sm) var(--radius-sm);
  box-shadow: var(--shadow);
  max-height: 350px;
  overflow-y: auto;
  z-index: 1000;
  display: none;
}
.autocomplete-suggestions.active {
  display: block;
}
.autocomplete-item {
  padding: 0.75rem;
  cursor: pointer;
  border-bottom: 1px solid var(--bg);
  font-size: 0.85rem;
  transition: background 0.15s;
}
.autocomplete-item:last-child {
  border-bottom: none;
}
.autocomplete-item:hover,
.autocomplete-item.selected {
  background: var(--bg);
}
.autocomplete-item strong {
  color: var(--primary);
  font-weight: 700;
}
.autocomplete-item small {
  display: block;
  color: var(--text-light);
  font-size: 0.75rem;
  margin-top: 0.15rem;
}

.busqueda-bar button { 
  background: var(--primary); 
  color: #fff; 
  border: none; 
  border-radius: var(--radius-sm); 
  padding: 0.55rem 1.25rem; 
  font-weight: 600; 
  cursor: pointer; 
  transition: background 0.2s;
  white-space: nowrap;
}
.busqueda-bar button:hover { 
  background: var(--primary-dark); 
}
.busqueda-bar button.btn-limpiar {
  background: var(--text-light);
  padding: 0.55rem 1rem;
}
.busqueda-bar button.btn-limpiar:hover {
  background: var(--text);
}
.busqueda-actions {
  display: flex;
  gap: 0.5rem;
  align-items: flex-end;
  flex-shrink: 0;
  margin-bottom: 1.25rem;
}

.search-tips {
  font-size: 0.7rem;
  color: var(--text-light);
  margin-top: 0.25rem;
  font-style: italic;
  min-height: 1rem;
  line-height: 1rem;
}
.search-tips i {
  margin-right: 0.25rem;
  color: var(--accent);
}
.search-tips strong {
  color: var(--primary);
  font-weight: 600;
  font-style: normal;
}

.nav-section { 
  display: flex; 
  gap: 0.75rem; 
  align-items: center; 
  padding: 0.75rem 0;
  background: var(--surface);
  border-radius: var(--radius-sm);
  margin-top: 1rem;
}
#btn-regresar { 
  background: var(--primary); 
  color: #fff; 
  border: none; 
  border-radius: var(--radius-sm); 
  padding: 0.45rem 0.9rem; 
  font-size: 0.85rem; 
  cursor: pointer; 
  display: none; 
  font-weight: 600;
  transition: background 0.2s;
}
#btn-regresar:hover { 
  background: var(--primary-dark); 
}
.breadcrumb-link { 
  color: var(--primary);
  text-decoration: none; 
  font-weight: 600;
  font-size: 0.9rem;
}
.breadcrumb-link:hover {
  text-decoration: underline;
  color: var(--primary-dark);
}
.breadcrumb-current { 
  color: var(--text); 
  font-weight: 600; 
  font-size: 0.9rem;
}
.breadcrumb-separator { 
  color: var(--text-light); 
  margin: 0 0.25rem;
  font-weight: 600;
}

#categorias-container { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
  gap: 0.875rem; 
}
.categoria, .tipologia-card { 
  background: var(--surface); 
  border: 1px solid var(--border); 
  border-radius: var(--radius); 
  padding: 0.875rem; 
  cursor: pointer; 
  transition: all 0.2s; 
  box-shadow: 0 1px 4px rgba(0,0,0,0.05); 
}
.categoria:hover, .tipologia-card:hover { 
  transform: translateY(-2px); 
  box-shadow: var(--shadow); 
  border-color: var(--accent); 
}
.categoria-imagen-wrapper {
  position: relative;
  width: 100%;
  height: 110px;
  margin-bottom: 0.5rem;
  background: var(--bg);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius-sm);
}
.categoria-imagen { 
  width: 100%; 
  height: 100%;
  object-fit: cover; 
  border-radius: var(--radius-sm); 
}
.imagen-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ef 100%);
  color: var(--text-light);
  font-size: 0.75rem;
  text-align: center;
  border-radius: var(--radius-sm);
  padding: 0.5rem;
  flex-direction: column;
}
.imagen-placeholder i {
  font-size: 2rem;
  opacity: 0.3;
  display: block;
  margin-bottom: 0.25rem;
}
.categoria-info h3 { 
  font-size: 0.9rem; 
  margin-bottom: 0.25rem; 
  line-height: 1.3;
}
.categoria-info p { 
  font-size: 0.7rem; 
  color: var(--text-light); 
  line-height: 1.4;
}

.zoom-icon { 
  position: absolute; 
  top: 0.5rem; 
  right: 0.5rem; 
  background: rgba(0,0,0,0.7); 
  color: #fff; 
  width: 26px; 
  height: 26px; 
  border-radius: 50%; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  font-size: 0.95rem; 
  cursor: pointer; 
  transition: background 0.2s;
  z-index: 10;
}
.zoom-icon:hover { 
  background: var(--accent); 
}
.btn-add-to-cart { 
  background: var(--accent); 
  color: #fff; 
  border: none; 
  border-radius: var(--radius-sm); 
  padding: 0.5rem 1rem; 
  font-weight: 600; 
  cursor: pointer; 
  width: 100%; 
  margin-top: 0.5rem; 
  font-size: 0.85rem;
  transition: background 0.2s;
}
.btn-add-to-cart:hover { 
  background: var(--primary); 
}
.btn-add-to-cart:disabled {
  background: var(--text-light);
  cursor: not-allowed;
  opacity: 0.6;
}

.tabla-generica { 
  overflow-x: auto; 
  border-radius: var(--radius-sm); 
  margin-top: 0.75rem; 
  border: 1px solid var(--border);
}
.tabla-generica table { 
  width: 100%; 
  border-collapse: collapse; 
  font-size: 0.8rem; 
}
.tabla-generica th, .tabla-generica td { 
  padding: 0.6rem 0.75rem; 
  text-align: left; 
  border-bottom: 1px solid var(--border); 
  vertical-align: middle;
}
.tabla-generica th { 
  background: var(--bg); 
  font-weight: 600; 
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  color: var(--text-light);
}
.tabla-generica tbody tr:last-child td {
  border-bottom: none;
}
.tabla-generica tbody tr:hover {
  background: var(--bg);
}
.tabla-generica img { 
  width: 45px; 
  height: 45px; 
  object-fit: cover; 
  border-radius: var(--radius-sm); 
  background: var(--bg);
}
.tabla-imagen-wrapper {
  position: relative;
  width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--bg);
  border-radius: var(--radius-sm);
}
.tabla-imagen-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ef 100%);
  color: var(--text-light);
  font-size: 0.65rem;
  border-radius: var(--radius-sm);
}
.tabla-imagen-placeholder i {
  font-size: 1.25rem;
  opacity: 0.3;
}
.loader, .mensaje-vacio { 
  text-align: center; 
  padding: 2rem; 
  color: var(--text-light); 
  font-size: 0.95rem; 
}

.categoria-badge {
  display: inline-block;
  padding: 0.25rem 0.6rem;
  background: var(--primary);
  color: white;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.categoria-badge.sin-categoria {
  background: var(--text-light);
}

#modalZoomImg { 
  position: fixed; 
  inset: 0; 
  background: rgba(0,0,0,0.85); 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  z-index: 200; 
  visibility: hidden; 
  opacity: 0; 
  transition: opacity 0.2s; 
}
#modalZoomImg.active { 
  visibility: visible; 
  opacity: 1; 
}
#modalZoomImg img { 
  max-width: 90vw; 
  max-height: 80vh; 
  border-radius: 12px; 
  border: 6px solid #fff; 
}
#modalZoomImg .close-modal { 
  position: absolute; 
  top: 1rem; 
  right: 1.5rem; 
  color: #fff; 
  font-size: 2.5rem; 
  background: none; 
  border: none; 
  cursor: pointer; 
}

#notificacion-container {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.notificacion-toast {
  background: var(--success);
  color: #fff;
  padding: 0.75rem 1.25rem;
  border-radius: var(--radius-sm);
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  font-weight: 600;
  font-size: 0.9rem;
  opacity: 0;
  transform: translateX(100%);
  transition: all 0.3s ease;
}
.notificacion-toast.show {
  opacity: 1;
  transform: translateX(0);
}
.notificacion-toast.error {
  background: var(--danger);
}

.tipologia-detalle { }
.tipologia-body {
  display: flex;
  flex-direction: row;
  gap: 1.5rem;
}
.tipologia-col-media {
  width: 40%;
  min-width: 300px;
  position: sticky;
  top: 150px;
  align-self: flex-start;
}
.tipologia-col-media img {
  width: 100%;
  height: auto;
  max-height: 400px;
  object-fit: contain;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  cursor: pointer;
  background: var(--bg);
}
.tipologia-imagen-placeholder {
  width: 100%;
  height: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ef 100%);
  color: var(--text-light);
  border-radius: var(--radius);
  border: 1px solid var(--border);
}
.tipologia-imagen-placeholder i {
  font-size: 4rem;
  opacity: 0.3;
  margin-bottom: 1rem;
}
.tipologia-imagen-placeholder span {
  font-size: 0.9rem;
  font-weight: 600;
}
.tipologia-col-info {
  flex: 1;
  min-width: 0;
}
.tipologia-info h2 {
  font-size: 1.5rem;
  color: var(--text);
  margin-bottom: 0.5rem;
  line-height: 1.3;
}
.tipologia-info .meta {
  font-size: 0.85rem;
  color: var(--text-light);
  margin-bottom: 1.5rem;
  border-bottom: 1px solid var(--border);
  padding-bottom: 1rem;
}
.tipologia-footer {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--surface);
  border-top: 1px solid var(--border);
  box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
  padding: 1rem 1.25rem;
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 1.5rem;
  z-index: 100;
}
.tipologia-total .label {
  font-size: 0.8rem;
  color: var(--text-light);
  text-transform: uppercase;
  font-weight: 600;
  text-align: right;
}
.tipologia-total .valor {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--primary);
  text-align: right;
}
.btn-agregar-tipologia {
  background: var(--success);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  padding: 0.75rem 1.5rem;
  font-weight: 700;
  cursor: pointer;
  font-size: 1rem;
  transition: background 0.2s;
}
.btn-agregar-tipologia:hover {
  background: var(--success-dark);
}
.btn-agregar-tipologia:disabled {
  background: var(--text-light);
  cursor: not-allowed;
  opacity: 0.6;
}
.tabla-hijos {
  margin-top: 1rem;
}
.tabla-hijos table {
  background: #fff;
}
.tabla-hijos tbody tr {
  transition: background 0.15s;
}
.tabla-hijos tbody tr td {
  font-size: 0.8rem;
}
.tabla-hijos th {
  background: var(--bg);
}
.sin-precio-msg {
  background: #fef3c7;
  border: 1px solid #f59e0b;
  color: #92400e;
  padding: 0.75rem 1rem;
  border-radius: var(--radius-sm);
  margin-top: 1rem;
  font-size: 0.85rem;
  font-weight: 600;
  text-align: center;
}

@media (max-width: 768px) {
  .busqueda-bar { 
    flex-direction: column; 
  }
  .campo { 
    min-width: 100%; 
  }
  .busqueda-actions {
    width: 100%;
  }
  .busqueda-actions button {
    flex: 1;
  }
  #categorias-container { 
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
  .catalogo-header {
    padding: 0.75rem;
  }
  .container {
    padding: 0.75rem;
    padding-bottom: 120px;
  }
  .tipologia-body {
    flex-direction: column;
  }
  .tipologia-col-media {
    width: 100%;
    position: static;
  }
  .tipologia-col-media img,
  .tipologia-imagen-placeholder {
    max-height: 250px;
    height: 250px;
  }
  .tipologia-footer {
    flex-direction: column;
    gap: 0.75rem;
    padding: 0.75rem;
  }
  .tipologia-total {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .tipologia-total .label { text-align: left; }
  .tipologia-total .valor { font-size: 1.25rem; text-align: right;}
  .btn-agregar-tipologia {
    width: 100%;
  }
}
</style>
</head>
<body>

<div class="catalogo-header">
  <form class="busqueda-bar" id="busqueda-bar" onsubmit="buscarProductos(event)">
    <div class="campo">
      <label for="busq-codigo">Código</label>
      <input type="text" id="busq-codigo" placeholder="Ej: LKSU010010" autocomplete="off">
      <div class="autocomplete-suggestions" id="sugerencias-codigo"></div>
      <div class="search-tips"><i class="fas fa-info-circle"></i> Escribe 3+ caracteres para buscar</div>
    </div>
    <div class="campo">
      <label for="busq-descripcion">Descripción</label>
      <input type="text" id="busq-descripcion" placeholder="Escribe para ver opciones disponibles..." autocomplete="off" style="text-transform: uppercase;">
      <div class="autocomplete-suggestions" id="sugerencias-descripcion"></div>
      <div class="search-tips">
        <i class="fas fa-info-circle"></i> 
        <span id="search-context-tip">Escribe y selecciona de la lista</span>
      </div>
    </div>
    <div class="campo">
      <label for="busq-listaprecio">Lista</label>
      <select id="busq-listaprecio"><option value="">Todas</option></select>
      <div class="search-tips" style="visibility: hidden;">.</div>
    </div>
    <div class="busqueda-actions">
      <button type="submit"><i class="fas fa-search"></i> <span id="btn-buscar-text">Buscar</span></button>
      <button type="button" class="btn-limpiar" onclick="limpiarFiltros()"><i class="fas fa-times"></i> Limpiar</button>
    </div>
  </form>
  
  <div class="nav-section">
    <button id="btn-regresar">← Volver</button>
    <div id="breadcrumb-container"></div>
  </div>
</div>

<div class="container">
  <div id="content-container"><div class="loader">Cargando catálogo...</div></div>
</div>

<div id="notificacion-container"></div>
<div id="modalZoomImg">
  <button class="close-modal" onclick="cerrarZoomImg()">×</button>
  <img src="" alt="Zoom">
</div>

<script>
const contentContainer = document.getElementById('content-container');
const breadcrumbContainer = document.getElementById('breadcrumb-container');
const btnRegresar = document.getElementById('btn-regresar');
const notificacionContainer = document.getElementById('notificacion-container');
window.carrito = [];
let historialNavegacion = [{id:null, nombre:'Inicio'}];
const urlParams = new URLSearchParams(window.location.search);
const seccionId = urlParams.get('seccion_id');
const padreIdTipologia = urlParams.get('padre_id');

// Variable para mantener el contexto actual de categoría
let categoriaActual = null;

// ============================================================================
// HELPER: obtener lista de precio seleccionada actualmente
// Se usa en TODAS las llamadas relacionadas con tipologías para pasarla al API.
// ============================================================================
function obtenerListaSeleccionada() {
  return document.getElementById('busq-listaprecio').value || '';
}

/**
 * Actualiza la interfaz de búsqueda según el contexto de navegación.
 */
function actualizarUIBusqueda() {
  const btnBuscarText = document.getElementById('btn-buscar-text');
  const searchContextTip = document.getElementById('search-context-tip');
  
  if (categoriaActual) {
    const categoriaInfo = historialNavegacion[historialNavegacion.length - 1];
    btnBuscarText.textContent = 'Buscar aquí';
    searchContextTip.innerHTML = `🎯 Buscando solo en: <strong>${categoriaInfo.nombre}</strong>`;
  } else {
    btnBuscarText.textContent = 'Buscar';
    searchContextTip.textContent = 'Escribe y selecciona de la lista';
  }
}

const API_USER = 'root';
const API_PASS = '12345678';

const LISTAS_PRECIO = [
  {"estado":true,"moneda":"COP","nombre":"MEPAL_CO_Nacionales","pais":"COLOMBIA"},
  {"estado":true,"moneda":"USD","nombre":"MEPAL_CO_Nacionales USD","pais":"COLOMBIA"},
  {"estado":true,"moneda":"USD","nombre":"MEPAL_CO_Sur America","pais":"COLOMBIA"},
  {"estado":true,"moneda":"USD","nombre":"MEPAL_EC_Nacionales","pais":"ECUADOR"}
];

// ============================================================================
// SISTEMA DE CARGA DE CATEGORÍAS VÍA API
// ============================================================================
let categoriasMap = {};
let categoriasLoaded = false;

// Cache de todos los productos para autocompletado
let todosLosProductosCache = [];
// Cache de productos por categoría
let productosPorCategoria = {};

async function cargarCategorias() {
  console.log('🔄 Iniciando carga de categorías desde API...');
  
  try {
    const response = await fetchConCredenciales('api.php?accion=obtener_categorias');
    
    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.error) {
      throw new Error(data.mensaje || 'Error al cargar categorías');
    }
    
    console.log('📦 JSON recibido, total de items:', data.length);
    
    categoriasMap = {};
    let contadorCategorias = 0;
    
    data.forEach(item => {
      if (item.codigo && item.categoria) {
        const codigoOriginal = item.codigo;
        const codigoStr = String(codigoOriginal).trim();
        const categoria = item.categoria;
        
        categoriasMap[codigoOriginal] = categoria;
        categoriasMap[codigoStr] = categoria;
        categoriasMap[codigoStr.toUpperCase()] = categoria;
        categoriasMap[codigoStr.toLowerCase()] = categoria;
        
        const codigoNum = parseInt(codigoStr, 10);
        if (!isNaN(codigoNum)) {
          categoriasMap[codigoNum] = categoria;
        }
        
        contadorCategorias++;
      }
    });
    
    categoriasLoaded = true;
    console.log(`✅ Categorías cargadas: ${contadorCategorias} productos únicos`);
    
    return true;
  } catch (error) {
    console.error('❌ Error al cargar categorías:', error);
    categoriasLoaded = false;
    return false;
  }
}

// ============================================================================
// SISTEMA DE BÚSQUEDA INTELIGENTE
// ============================================================================

function normalizarTexto(texto) {
  if (!texto) return '';
  return texto
    .toLowerCase()
    .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
    .replace(/\s+/g, ' ')
    .trim();
}

function normalizarDimensiones(texto) {
  return texto.replace(/(\d+)\s*[xX×]\s*(\d+)/g, '$1x$2');
}

function coincideTextoFlexible(texto, busqueda) {
  if (!texto || !busqueda) return false;
  
  const textoNorm = normalizarTexto(texto);
  const busquedaNorm = normalizarTexto(busqueda);
  const textoConDim = normalizarDimensiones(textoNorm);
  const busquedaConDim = normalizarDimensiones(busquedaNorm);
  
  if (textoConDim.includes(busquedaConDim)) return true;
  
  const palabrasBusqueda = busquedaConDim.split(' ').filter(p => p.length > 0);
  return palabrasBusqueda.every(palabra => textoConDim.includes(palabra));
}

function calcularRelevancia(item, busquedaCodigo, busquedaDescripcion) {
  let puntuacion = 0;
  const codigo = item.codigo || item.producto_codigo || '';
  const descripcion = item.producto_descripcion || item.descripcion || '';
  
  if (busquedaCodigo) {
    const busqNorm = normalizarTexto(busquedaCodigo);
    const codNorm = normalizarTexto(codigo);
    if (codNorm === busqNorm)            puntuacion += 1000;
    else if (codNorm.startsWith(busqNorm)) puntuacion += 500;
    else if (codNorm.includes(busqNorm))   puntuacion += 100;
  }
  
  if (busquedaDescripcion) {
    const busqNorm = normalizarTexto(busquedaDescripcion);
    const descNorm = normalizarTexto(descripcion);
    const busqConDim = normalizarDimensiones(busqNorm);
    const descConDim = normalizarDimensiones(descNorm);
    
    if (descConDim.includes(busqConDim))      puntuacion += 800;
    const palabras = busqConDim.split(' ').filter(p => p.length > 0);
    if (palabras.every(p => descConDim.includes(p))) puntuacion += 400;
    palabras.forEach(p => { if (descConDim.includes(p)) puntuacion += 50; });
    if (descConDim.startsWith(busqConDim))    puntuacion += 200;
  }
  
  return puntuacion;
}

function buscarEnCache(busquedaCodigo, busquedaDescripcion, busquedaLista) {
  let resultados = categoriaActual && productosPorCategoria[categoriaActual]
    ? productosPorCategoria[categoriaActual]
    : todosLosProductosCache;
  
  console.log(`🔍 Buscando en ${categoriaActual ? 'categoría '+categoriaActual : 'todo el cache'}`);
  console.log(`📦 Productos disponibles: ${resultados.length}`);
  
  if (busquedaCodigo) {
    resultados = resultados.filter(item =>
      coincideTextoFlexible(item.codigo || item.producto_codigo || '', busquedaCodigo)
    );
  }
  if (busquedaDescripcion) {
    resultados = resultados.filter(item =>
      coincideTextoFlexible(item.producto_descripcion || item.descripcion || '', busquedaDescripcion)
    );
  }
  if (busquedaLista) {
    resultados = resultados.filter(item => (item.lista || '') === busquedaLista);
  }
  
  resultados = resultados.map(item => ({
    ...item,
    _relevancia: calcularRelevancia(item, busquedaCodigo, busquedaDescripcion)
  }));
  resultados.sort((a, b) => b._relevancia - a._relevancia);
  
  console.log(`✅ Resultados encontrados: ${resultados.length}`);
  return resultados;
}

function generarSugerencias(campo, valor) {
  if (!valor || valor.length < 3) return [];
  
  const sugerencias = [];
  const yaAgregados = new Set();
  
  let productosDisponibles = categoriaActual && productosPorCategoria[categoriaActual]
    ? productosPorCategoria[categoriaActual]
    : todosLosProductosCache;
  
  productosDisponibles.forEach(item => {
    if (campo === 'codigo') {
      const codigo = item.codigo || item.producto_codigo || '';
      const codigoNorm = normalizarTexto(codigo);
      if (coincideTextoFlexible(codigo, valor) && !yaAgregados.has(codigoNorm)) {
        sugerencias.push({ valor: codigo, descripcion: item.producto_descripcion || item.descripcion || '' });
        yaAgregados.add(codigoNorm);
      }
    } else if (campo === 'descripcion') {
      const descripcion = item.producto_descripcion || item.descripcion || '';
      if (coincideTextoFlexible(descripcion, valor)) {
        const key = normalizarTexto(descripcion);
        if (!yaAgregados.has(key)) {
          sugerencias.push({
            valor: descripcion,
            descripcion: `${item.codigo || item.producto_codigo || 'N/A'}`,
            codigo: item.codigo || item.producto_codigo || ''
          });
          yaAgregados.add(key);
        }
      }
    }
  });
  
  sugerencias.sort((a, b) => {
    const rA = calcularRelevancia(
      {codigo: a.codigo || a.valor, descripcion: a.valor},
      campo === 'codigo' ? valor : '', campo === 'descripcion' ? valor : ''
    );
    const rB = calcularRelevancia(
      {codigo: b.codigo || b.valor, descripcion: b.valor},
      campo === 'codigo' ? valor : '', campo === 'descripcion' ? valor : ''
    );
    return rB - rA;
  });
  
  return sugerencias.slice(0, 10);
}

// ============================================================================
// AUTOCOMPLETADO
// ============================================================================
let autocompletadoActivo = null;
let sugerenciaSeleccionada = -1;

function mostrarSugerencias(campo, sugerencias) {
  const input = document.getElementById(`busq-${campo}`);
  const contenedor = document.getElementById(`sugerencias-${campo}`);
  
  if (!sugerencias || sugerencias.length === 0) {
    contenedor.classList.remove('active');
    return;
  }
  
  contenedor.innerHTML = '';
  sugerencias.forEach((sug, index) => {
    const item = document.createElement('div');
    item.className = 'autocomplete-item';
    const valor = input.value.toLowerCase();
    const valorEscapado = valor.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${valorEscapado})`, 'gi');
    const textoResaltado = sug.valor.replace(regex, '<strong>$1</strong>');
    
    if (campo === 'descripcion') {
      item.innerHTML = `
        <div style="font-weight: 600; margin-bottom: 0.25rem;">${textoResaltado}</div>
        <small style="display: flex; justify-content: space-between; align-items: center;">
          <span style="color: var(--primary);">📦 ${sug.descripcion}</span>
        </small>`;
    } else {
      item.innerHTML = `<div>${textoResaltado}</div>${sug.descripcion ? `<small>${sug.descripcion}</small>` : ''}`;
    }
    
    item.onclick = () => {
      contenedor.classList.remove('active');
      autocompletadoActivo = null;
      if (campo === 'descripcion' && sug.codigo) {
        // -------------------------------------------------------------------
        // CAMBIO: al seleccionar desde autocompletado, pasar la lista actual
        // -------------------------------------------------------------------
        const categoria = obtenerCategoria(sug.codigo);
        cargarDetalleTipologia(sug.codigo, sug.valor, categoria || '', obtenerListaSeleccionada());
      } else {
        input.value = sug.valor;
        if (campo === 'descripcion') {
          setTimeout(() => {
            document.getElementById('busqueda-bar').dispatchEvent(new Event('submit', {cancelable: true}));
          }, 100);
        }
      }
    };
    
    item.addEventListener('mouseenter', () => {
      sugerenciaSeleccionada = index;
      actualizarSeleccionSugerencia(contenedor);
    });
    
    contenedor.appendChild(item);
  });
  
  contenedor.classList.add('active');
  autocompletadoActivo = campo;
  sugerenciaSeleccionada = -1;
}

function actualizarSeleccionSugerencia(contenedor) {
  contenedor.querySelectorAll('.autocomplete-item').forEach((item, index) => {
    item.classList.toggle('selected', index === sugerenciaSeleccionada);
  });
}

function ocultarSugerencias(campo) {
  setTimeout(() => {
    document.getElementById(`sugerencias-${campo}`).classList.remove('active');
    autocompletadoActivo = null;
  }, 200);
}

['codigo', 'descripcion'].forEach(campo => {
  const input = document.getElementById(`busq-${campo}`);
  
  input.addEventListener('input', (e) => {
    if (campo === 'descripcion') {
      const cursorPos = e.target.selectionStart;
      e.target.value = e.target.value.toUpperCase();
      e.target.setSelectionRange(cursorPos, cursorPos);
    }
    const valor = e.target.value;
    if (valor.length >= 3) mostrarSugerencias(campo, generarSugerencias(campo, valor));
    else ocultarSugerencias(campo);
  });
  
  if (campo === 'descripcion') {
    input.addEventListener('blur', () => {
      setTimeout(() => {
        if (!autocompletadoActivo || autocompletadoActivo !== campo) {
          input.value = '';
        }
      }, 300);
    });
  }
  
  input.addEventListener('keydown', (e) => {
    if (!autocompletadoActivo || autocompletadoActivo !== campo) return;
    const contenedor = document.getElementById(`sugerencias-${campo}`);
    const items = contenedor.querySelectorAll('.autocomplete-item');
    
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      sugerenciaSeleccionada = Math.min(sugerenciaSeleccionada + 1, items.length - 1);
      actualizarSeleccionSugerencia(contenedor);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      sugerenciaSeleccionada = Math.max(sugerenciaSeleccionada - 1, -1);
      actualizarSeleccionSugerencia(contenedor);
    } else if (e.key === 'Enter') {
      if (sugerenciaSeleccionada >= 0 && items[sugerenciaSeleccionada]) {
        e.preventDefault();
        items[sugerenciaSeleccionada].click();
      }
    } else if (e.key === 'Escape') {
      ocultarSugerencias(campo);
    }
  });
});

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================
function obtenerCategoria(codigo) {
  if (!codigo) return null;
  const codigoStr = String(codigo).trim();
  return categoriasMap[codigo]
    || categoriasMap[codigoStr]
    || categoriasMap[parseInt(codigo, 10)]
    || null;
}

function precioValido(precio) {
  const p = parseFloat(precio);
  return !isNaN(p) && p > 0;
}

async function fetchConCredenciales(url, options = {}) {
  return fetch(url, {
    ...options,
    headers: {
      'Authorization': 'Basic ' + btoa(API_USER + ':' + API_PASS),
      ...options.headers
    }
  });
}

function cargarListasPrecio() {
  const select = document.getElementById('busq-listaprecio');
  select.innerHTML = '<option value="">Todas</option>';
  LISTAS_PRECIO.filter(lp => lp.estado).forEach(lp => {
    const opc = document.createElement("option");
    opc.value = lp.nombre;
    opc.textContent = lp.nombre + " (" + lp.moneda + ")";
    select.appendChild(opc);
  });
}

function limpiarFiltros() {
  document.getElementById('busq-codigo').value = '';
  document.getElementById('busq-descripcion').value = '';
  document.getElementById('busq-listaprecio').value = '';
  
  if (categoriaActual) {
    const categoriaInfo = historialNavegacion[historialNavegacion.length - 1];
    navegarA(categoriaActual, categoriaInfo.nombre);
  } else {
    cargarNivelPrincipal();
  }
  mostrarNotificacion('Filtros limpiados', 'success');
}

function mostrarNotificacion(mensaje, tipo = 'success') {
  const toast = document.createElement('div');
  toast.className = `notificacion-toast ${tipo === 'error' ? 'error' : ''}`;
  toast.textContent = mensaje;
  notificacionContainer.appendChild(toast);
  setTimeout(() => toast.classList.add('show'), 10);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

function mostrarSiErrores(data) {
  if (data.error) mostrarNotificacion(data.error, 'error');
}

function validarImagenUrl(url) {
  if (!url || url === '' || url === 'null' || url === 'undefined') return null;
  try { new URL(url); return url; } catch (e) { return null; }
}

// ============================================================================
// BÚSQUEDA PRINCIPAL
// Envía la lista de precio al backend para que el API elija el JSON correcto.
// ============================================================================
function buscarProductos(e) {
  e.preventDefault();
  const codigo      = document.getElementById('busq-codigo').value.trim();
  const descripcion = document.getElementById('busq-descripcion').value.trim();
  const listaPrecio = obtenerListaSeleccionada();
  
  if (!codigo && !descripcion && !listaPrecio) {
    mostrarNotificacion('Por favor ingrese al menos un criterio de búsqueda', 'error');
    return;
  }
  
  console.log('🔍 Búsqueda:', {codigo, descripcion, listaPrecio, categoriaActual});
  
  // Si hay categoría activa, siempre ir a la API para que filtre correctamente
  if (categoriaActual) {
    realizarBusquedaAPI(codigo, descripcion, listaPrecio, categoriaActual);
    return;
  }
  
  // Sin categoría activa: intentar cache local primero
  if (todosLosProductosCache.length > 0) {
    const resultadosLocales = buscarEnCache(codigo, descripcion, listaPrecio);
    console.log(`📊 Resultados locales: ${resultadosLocales.length}`);
    if (resultadosLocales.length > 0) {
      renderizarProductos(resultadosLocales);
      renderizarBreadcrumbs();
      return;
    }
    contentContainer.innerHTML = '<div class="mensaje-vacio">No se encontraron resultados en el cache local.</div>';
    return;
  }
  
  // Sin cache: búsqueda global en API
  realizarBusquedaAPI(codigo, descripcion, listaPrecio, null);
}

/**
 * Llama al endpoint buscar_productos del API.
 * Incluye siempre listaPrecio en el payload para que el backend
 * seleccione el JSON de tipologías correcto.
 */
function realizarBusquedaAPI(codigo, descripcion, listaPrecio, categoriaId) {
  const payload = { codigo, descripcion, listaPrecio };
  if (categoriaId) payload.categoria_id = categoriaId;
  
  contentContainer.innerHTML = '<div class="loader">Buscando...</div>';
  
  fetchConCredenciales('api.php?accion=buscar_productos', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(payload)
  })
  .then(r => r.json()).then(d => {
    mostrarSiErrores(d);
    if (d.results?.length > 0) {
      console.log(`✅ API retornó ${d.results.length} resultados (archivo: ${d.archivo_tipologias})`);
      renderizarProductos(d.results);
      renderizarBreadcrumbs();
    } else {
      const msg = categoriaId
        ? 'No se encontraron resultados en esta categoría con los criterios especificados.'
        : 'No se encontraron resultados con los criterios especificados.';
      contentContainer.innerHTML = `<div class="mensaje-vacio">${msg}</div>`;
    }
  }).catch(err => {
    console.error('Error en búsqueda:', err);
    mostrarNotificacion('Error en búsqueda', 'error');
  });
}

function abrirZoomImg(src) {
  const srcValida = validarImagenUrl(src);
  if (!srcValida) return;
  const m = document.getElementById('modalZoomImg');
  m.querySelector('img').src = srcValida;
  m.classList.add('active');
}

function cerrarZoomImg() {
  const m = document.getElementById('modalZoomImg');
  m.classList.remove('active');
  setTimeout(() => { m.querySelector('img').src = ''; }, 200);
}

document.getElementById('modalZoomImg').addEventListener('click', function(e) {
  if (e.target === this) cerrarZoomImg();
});

function renderizarCategorias(cats) {
  contentContainer.innerHTML = '<div id="categorias-container"></div>';
  const cont = document.getElementById('categorias-container');
  if (!cats.length) { cont.innerHTML = '<div class="mensaje-vacio">Sin subcategorías.</div>'; return; }
  
  cats.forEach(c => {
    const meta = `<p><strong>ID:</strong> ${c.id||''} ${c.padre_id ? `| <strong>Padre:</strong> ${c.padre_id}` : ''}</p>`;
    const imgUrl = validarImagenUrl(c.imagen?.urlubicacion || c.imagen?.url || c.imagen?.ubicacion);
    
    if (!imgUrl) {
      const b = document.createElement('button');
      b.className = 'btn-add-to-cart';
      b.innerHTML = `<strong>${c.nombre}</strong>${meta}`;
      b.onclick = () => navegarA(c.id, c.nombre);
      cont.appendChild(b);
    } else {
      const card = document.createElement('div');
      card.className = 'categoria';
      card.innerHTML = `<div class="categoria-imagen-wrapper">
        <img src="${imgUrl}" alt="${c.nombre}" class="categoria-imagen"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="imagen-placeholder" style="display:none;"><i class="fas fa-image"></i><span>Sin imagen</span></div>
        <span class="zoom-icon" onclick="event.stopPropagation(); abrirZoomImg('${imgUrl}')"><i class="fas fa-search-plus"></i></span>
      </div><div class="categoria-info"><h3>${c.nombre}</h3>${meta}</div>`;
      card.onclick = e => { if (e.target.closest('.zoom-icon')) return; navegarA(c.id, c.nombre); };
      cont.appendChild(card);
    }
  });
}

function resaltarTexto(texto, busqueda) {
  if (!texto || !busqueda) return texto;
  const busquedaNorm = normalizarTexto(busqueda);
  if (!normalizarTexto(texto).includes(busquedaNorm)) return texto;
  const palabras = busqueda.split(' ').filter(p => p.length > 0);
  let resultado = texto;
  palabras.forEach(palabra => {
    if (palabra.length >= 2) {
      const palabraEscapada = palabra.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      resultado = resultado.replace(
        new RegExp(`(${palabraEscapada})`, 'gi'),
        '<mark style="background: #fef08a; color: #854d0e; font-weight: 600; padding: 0 2px;">$1</mark>'
      );
    }
  });
  return resultado;
}

function renderizarProductos(prods) {
  if (!prods.length) { contentContainer.innerHTML = '<div class="mensaje-vacio">Sin productos.</div>'; return; }
  
  const codigoBusqueda      = document.getElementById('busq-codigo')?.value || '';
  const descripcionBusqueda = document.getElementById('busq-descripcion')?.value || '';
  
  let html = `<div class="tabla-generica"><table><thead><tr>
    <th>Imagen</th><th>Código</th><th>Categoría</th><th>Precio</th>
    <th>Descripción</th><th>Lista</th><th style="width:100px;"></th>
  </tr></thead><tbody>`;
  
  prods.forEach(p => {
    const imgUrl     = validarImagenUrl(p.img_url);
    const cod        = p.codigo || p.producto_codigo || 'N/A';
    const pre        = p.precio ?? 'N/A';
    const desc       = p.producto_descripcion || p.descripcion || 'Sin desc';
    const lista      = p.lista || 'N/A';
    const tipo       = p.tipo || 'producto';
    const tienePrecio = precioValido(pre);
    const categoria  = obtenerCategoria(cod);
    const codResaltado  = resaltarTexto(cod,  codigoBusqueda);
    const descResaltada = resaltarTexto(desc, descripcionBusqueda);
    
    const imagenHtml = imgUrl
      ? `<div class="tabla-imagen-wrapper">
           <img src="${imgUrl}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
           <div class="tabla-imagen-placeholder" style="display:none;"><i class="fas fa-image"></i></div>
           <span class="zoom-icon" onclick="event.stopPropagation(); abrirZoomImg('${imgUrl}')"
                 style="width:22px;height:22px;font-size:0.75rem;top:2px;right:2px;">
             <i class="fas fa-search-plus"></i>
           </span>
         </div>`
      : `<div class="tabla-imagen-wrapper">
           <div class="tabla-imagen-placeholder"><i class="fas fa-image"></i></div>
         </div>`;
    
    const categoriaHtml = categoria
      ? `<span class="categoria-badge">${categoria}</span>`
      : `<span class="categoria-badge sin-categoria">Sin categoría</span>`;
    
    // -------------------------------------------------------------------
    // CAMBIO: cargarDetalleTipologia recibe la lista actual como 4.° arg
    // -------------------------------------------------------------------
    html += `<tr>
      <td>${imagenHtml}</td>
      <td><strong>${codResaltado}</strong></td>
      <td>${categoriaHtml}</td>
      <td><strong>${tienePrecio ? '$' + Number(pre).toLocaleString('es-CO') : 'Sin precio'}</strong></td>
      <td>${descResaltada}</td>
      <td><small>${lista}</small></td>
      <td>${
        tipo === 'tipologia'
          ? `<button class="btn-add-to-cart"
               onclick="cargarDetalleTipologia('${cod}','${desc.replace(/'/g,"\\'")}','${categoria || ''}','${obtenerListaSeleccionada()}')">
               Ver Detalle
             </button>`
          : tienePrecio
            ? `<button class="btn-add-to-cart"
                 onclick="enviarAlCotizador(event,'${cod}','${desc.replace(/'/g,"\\'")}','${imgUrl || ''}',${pre},'${categoria || ''}')">
                 Agregar
               </button>`
            : `<button class="btn-add-to-cart" disabled title="Sin precio disponible">Sin precio</button>`
      }</td>
    </tr>`;
  });
  
  html += `</tbody></table></div>`;
  contentContainer.innerHTML = html;
}

function renderizarBreadcrumbs() {
  breadcrumbContainer.innerHTML = '';
  btnRegresar.style.display = historialNavegacion.length > 1 ? 'inline-block' : 'none';
  historialNavegacion.forEach((p, i) => {
    if (i === historialNavegacion.length - 1) {
      const s = document.createElement('span');
      s.className = 'breadcrumb-current';
      s.textContent = p.nombre;
      breadcrumbContainer.appendChild(s);
    } else {
      const a = document.createElement('a');
      a.className = 'breadcrumb-link';
      a.href = '#';
      a.textContent = p.nombre;
      a.onclick = e => {
        e.preventDefault();
        historialNavegacion = historialNavegacion.slice(0, i + 1);
        p.id === null ? cargarNivelPrincipal() : navegarA(p.id, p.nombre);
      };
      breadcrumbContainer.appendChild(a);
    }
    if (i < historialNavegacion.length - 1) {
      breadcrumbContainer.insertAdjacentHTML('beforeend', '<span class="breadcrumb-separator">›</span>');
    }
  });
}

function actualizarHistorialYUrl(id, nombre) {
  if (historialNavegacion[historialNavegacion.length - 1].id !== id) {
    historialNavegacion.push({id, nombre});
  }
  const u = new URL(window.location);
  id ? u.searchParams.set('padre', id) : u.searchParams.delete('padre');
  history.pushState({id, nombre}, nombre, u.toString());
}

btnRegresar.onclick = () => {
  if (historialNavegacion.length > 1) {
    historialNavegacion.pop();
    const p = historialNavegacion[historialNavegacion.length - 1];
    if (p.id === null) {
      cargarNivelPrincipal();
    } else {
      categoriaActual = p.id;
      navegarA(p.id, p.nombre);
    }
  }
};

window.onpopstate = () => {
  const p = new URLSearchParams(window.location.search).get('padre');
  p ? navegarA(p, 'Cat ' + p) : cargarNivelPrincipal();
};

function cargarNivelPrincipal() {
  contentContainer.innerHTML = '<div class="loader">Cargando...</div>';
  categoriaActual = null;
  actualizarUIBusqueda();
  
  fetchConCredenciales('api.php?accion=listar')
    .then(r => r.json()).then(d => {
      mostrarSiErrores(d);
      if (d.results?.length > 0) {
        renderizarCategorias(d.results);
        renderizarBreadcrumbs();
      } else {
        contentContainer.innerHTML = '<div class="mensaje-vacio">Sin categorías.</div>';
      }
    }).catch(err => {
      console.error('Error al cargar:', err);
      mostrarNotificacion('Error al cargar', 'error');
    });
}

function navegarA(id, nombre) {
  contentContainer.innerHTML = '<div class="loader">Cargando...</div>';
  categoriaActual = id;
  actualizarHistorialYUrl(id, nombre);
  renderizarBreadcrumbs();
  actualizarUIBusqueda();
  
  fetchConCredenciales(`api.php?accion=listar_hijos&padre_id=${id}`)
    .then(r => r.json()).then(d => {
      mostrarSiErrores(d);
      if (d.results?.length > 0) {
        renderizarCategorias(d.results);
      } else {
        Promise.all([
          fetchConCredenciales(`api.php?accion=listar_productos&categoria_id=${id}`).then(r => r.json()),
          fetchConCredenciales(`api.php?accion=listar_tipologias&categoria_id=${id}`).then(r => r.json())
        ]).then(([prodData, tipoData]) => {
          let hayProductos  = false;
          let hayTipologias = false;
          
          if (prodData.results?.length > 0) {
            productosPorCategoria[id] = prodData.results;
            prodData.results.forEach(prod => {
              const k = prod.codigo || prod.producto_codigo;
              if (!todosLosProductosCache.find(p => (p.codigo || p.producto_codigo) === k)) {
                todosLosProductosCache.push(prod);
              }
            });
            console.log(`📦 ${prodData.results.length} productos para categoría ${id}`);
            hayProductos = true;
          }
          
          if (tipoData.results?.length > 0) {
            productosPorCategoria[id] = [
              ...(productosPorCategoria[id] || []),
              ...tipoData.results
            ];
            tipoData.results.forEach(tipo => {
              const k = tipo.codigo || tipo.producto_codigo;
              if (!todosLosProductosCache.find(p => (p.codigo || p.producto_codigo) === k)) {
                todosLosProductosCache.push(tipo);
              }
            });
            console.log(`📦 ${tipoData.results.length} tipologías para categoría ${id}`);
            hayTipologias = true;
          }
          
          if (hayProductos)       renderizarProductos(prodData.results);
          else if (hayTipologias) renderizarTipologias(tipoData.results);
          else contentContainer.innerHTML = '<div class="mensaje-vacio">Sin contenido.</div>';
        }).catch(err => {
          console.error('Error cargando datos:', err);
          mostrarNotificacion('Error cargando datos', 'error');
        });
      }
    }).catch(err => {
      console.error('Error subcategorías:', err);
      mostrarNotificacion('Error subcategorías', 'error');
    });
}

function renderizarTipologias(tips) {
  contentContainer.innerHTML = '<div id="categorias-container"></div>';
  const cont = document.getElementById('categorias-container');
  if (!tips.length) { cont.innerHTML = '<div class="mensaje-vacio">Sin tipologías.</div>'; return; }
  
  tips.forEach(t => {
    const card = document.createElement('div');
    card.className = 'tipologia-card';
    const imgUrl = validarImagenUrl(t.imagen?.urlubicacion || t.imagen?.url || t.imagen?.ubicacion);
    const desc   = t.descripcion || 'Sin desc';
    const codigo = t.codigo || 'N/A';
    const categoria = obtenerCategoria(codigo);
    
    let imagenHtml = imgUrl
      ? `<img src="${imgUrl}" alt="${desc}" class="categoria-imagen"
              onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
         <div class="imagen-placeholder" style="display:none;"><i class="fas fa-image"></i><span>Sin imagen</span></div>
         <span class="zoom-icon" onclick="event.stopPropagation(); abrirZoomImg('${imgUrl}')">
           <i class="fas fa-search-plus"></i>
         </span>`
      : `<div class="imagen-placeholder"><i class="fas fa-image"></i><span>Sin imagen</span></div>`;
    
    card.innerHTML = `<div class="categoria-imagen-wrapper">${imagenHtml}</div>
                      <div class="categoria-info">
                        <h3>${desc}</h3>
                        <p><strong>Cód:</strong> ${codigo} | <strong>ID:</strong> ${t.categoria_tipologia_id||''}</p>
                      </div>`;
    // CAMBIO: pasar lista seleccionada al hacer click en tarjeta de tipología
    card.onclick = e => {
      if (e.target.closest('.zoom-icon')) return;
      cargarDetalleTipologia(codigo, desc, categoria, obtenerListaSeleccionada());
    };
    cont.appendChild(card);
  });
}

// ============================================================================
// cargarDetalleTipologia
// CAMBIO PRINCIPAL: ahora acepta y envía el parámetro `lista` al API.
// El API usará ese valor para elegir el JSON de tipologías correcto:
//   MEPAL_CO_Nacionales / MEPAL_CO_Nacionales USD → tipologias-detalle_Colombia.json
//   MEPAL_CO_Sur America                          → tipologias-detalle_Distribuidores.json
//   MEPAL_EC_Nacionales                           → tipologias-detalle_Ecuador.json
// ============================================================================
function cargarDetalleTipologia(cod, nom, categoria = '', lista = '') {
  console.log(`🔍 Detalle tipología: ${cod} | Lista: "${lista || '(default Colombia)'}" | Categoría: ${categoria}`);
  contentContainer.innerHTML = '<div class="loader">Cargando detalle...</div>';
  actualizarHistorialYUrl(null, nom);
  renderizarBreadcrumbs();
  
  // Construir URL con el parámetro ?lista= para que el API elija el JSON correcto
  const listaParam = lista ? `&lista=${encodeURIComponent(lista)}` : '';
  
  fetchConCredenciales(`api.php?accion=detalle_tipologia&codigo=${encodeURIComponent(cod)}${listaParam}`)
    .then(r => r.json()).then(d => {
      mostrarSiErrores(d);
      console.log(`📄 Archivo tipologías usado: ${d.archivo_tipologias || 'N/A'}`);
      if (d.results?.length > 0) {
        renderizarDetalleTipologia(d.results[0], categoria);
      } else if (d.error) {
        contentContainer.innerHTML = `<div class="mensaje-vacio">${d.error}</div>`;
      } else {
        contentContainer.innerHTML = '<div class="mensaje-vacio">Sin detalle.</div>';
      }
    }).catch(err => {
      console.error('Error detalle:', err);
      mostrarNotificacion('Error detalle', 'error');
    });
}

function renderizarDetalleTipologia(data, categoria = '') {
  let totalAcumulado = 0;
  
  if (data.hijos?.length > 0) {
    totalAcumulado = data.hijos.reduce((sum, h) => {
      const unitario = h.precio || 0;
      const cantidad = h.cantidad || 0;
      return sum + (unitario * cantidad);
    }, 0);
  }

  const tienePrecioValido = precioValido(totalAcumulado);
  const imgUrl = validarImagenUrl(data.imagen?.urlubicacion || data.imagen?.url || data.imagen?.ubicacion);
  const codigoTipologia = data.codigo || 'N/A';

  const imagenHtml = imgUrl
    ? `<img src="${imgUrl}" alt="${data.descripcion}"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
            onclick="abrirZoomImg('${imgUrl}')">
       <div class="tipologia-imagen-placeholder" style="display:none;">
         <i class="fas fa-image"></i><span>Sin imagen disponible</span>
       </div>`
    : `<div class="tipologia-imagen-placeholder">
         <i class="fas fa-image"></i><span>Sin imagen disponible</span>
       </div>`;

  let tablaHijosHtml = '';
  if (data.hijos?.length > 0) {
    tablaHijosHtml = `<div class="tabla-generica tabla-hijos">
      <table><thead><tr>
        <th>Código</th><th>Categoría</th><th>Descripción</th>
        <th style="width:120px;">Vlr. Unitario</th><th style="width:80px;">Cant.</th>
        <th style="width:120px;">Total</th>
      </tr></thead><tbody>`;

    data.hijos.forEach(h => {
      const p = h.producto || {};
      const unitario = h.precio || 0;
      const cantidad = h.cantidad || 0;
      const totalFila = unitario * cantidad;

      const codigoHijo = p.codigo || p.producto_codigo || 'N/A';
      const categoriaHijo = obtenerCategoria(codigoHijo);
      const catHtml = categoriaHijo
        ? `<span class="categoria-badge">${categoriaHijo}</span>`
        : `<span class="categoria-badge sin-categoria">Sin categoría</span>`;

      tablaHijosHtml += `<tr>
        <td><strong>${codigoHijo}</strong></td>
        <td>${catHtml}</td>
        <td>${p.descripcion || 'N/A'}</td>
        <td><strong>$${Number(unitario).toLocaleString('es-CO')}</strong></td>
        <td>${cantidad}</td>
        <td><strong>$${Number(totalFila).toLocaleString('es-CO')}</strong></td>
      </tr>`;
    });

    tablaHijosHtml += `</tbody></table></div>`;
  } else {
    tablaHijosHtml = `<div class="mensaje-vacio" style="margin-top:1rem;">No hay productos asociados</div>`;
  }

  const mensajeSinPrecio = !tienePrecioValido
    ? `<div class="sin-precio-msg">
         <i class="fas fa-exclamation-triangle"></i>
         Esta tipología no tiene precio disponible y no puede ser agregada al cotizador.
       </div>`
    : '';

  const dataParaAgregar = { ...data, precioTotalCalculado: totalAcumulado };

  contentContainer.innerHTML = `
    <div class="tipologia-detalle">
      <div class="tipologia-body">
        <div class="tipologia-col-media">${imagenHtml}</div>
        <div class="tipologia-col-info">
          <div class="tipologia-info">
            <h2>${data.descripcion || 'Sin descripción'}</h2>
            <div class="meta">
              <strong>Código:</strong> ${codigoTipologia} | 
              <strong>ID:</strong> ${data.categoria_tipologia_id || 'N/A'} | 
              <strong>Lista:</strong> ${data.lista || 'N/A'}
            </div>
          </div>
          ${tablaHijosHtml}
          ${mensajeSinPrecio}
        </div>
      </div>
    </div>
    <div class="tipologia-footer">
      <div class="tipologia-total">
        <div class="label">Total</div>
        <div class="valor">${tienePrecioValido ? '$' + totalAcumulado.toLocaleString('es-CO') : 'Sin precio'}</div>
      </div>
      <button class="btn-agregar-tipologia"
              onclick="agregarTipologiaCompleta(${JSON.stringify(dataParaAgregar).replace(/"/g, '&quot;')}, '${categoria}')"
              ${!tienePrecioValido ? 'disabled title="No se puede agregar sin precio"' : ''}>
        <i class="fas fa-${tienePrecioValido ? 'check' : 'ban'}"></i>
        ${tienePrecioValido ? 'Agregar al Cotizador' : 'Sin precio disponible'}
      </button>
    </div>`;
}
function agregarTipologiaCompleta(data, categoria = '') {
  if (!seccionId) { mostrarNotificacion('Falta ID de sección', 'error'); return; }
  
  let totalAcumulado = 0;
  if (data.hijos?.length > 0) {
    totalAcumulado = data.hijos.reduce((sum, h) => {
      return sum + ((h.precio || 0) * (h.cantidad || 0));
    }, 0);
  }
  
  if (!precioValido(totalAcumulado)) {
    mostrarNotificacion('No se puede agregar una tipología sin precio', 'error');
    return;
  }
  
  const subItems = (data.hijos || []).map(h => {
    const p = h.producto || {};
    const codigoHijo = p.codigo || p.producto_codigo || '';
    return {
      codigo: codigoHijo,
      descripcion: p.descripcion || 'N/A',
      unitario: h.precio || 0,
      cantidad: h.cantidad || 0,
      total: (h.precio || 0) * (h.cantidad || 0),
      categoria: obtenerCategoria(codigoHijo) || ''
    };
  });
  
  window.parent.postMessage({
    accion: 'agregarProducto',
    seccionId,
    padreId: padreIdTipologia || null,
    producto: {
      codigo: data.codigo || 'N/A',
      descripcion: data.descripcion || 'Sin descripción',
      imagen: validarImagenUrl(data.imagen?.urlubicacion || data.imagen?.url || data.imagen?.ubicacion) || '',
      precio: totalAcumulado,
      esTypologia: true,
      categoria: categoria || '',
      subItems
    }
  }, '*');
  
  mostrarNotificacion('✓ ¡Tipología agregada exitosamente!');
}
function enviarAlCotizador(event, codigo, descripcion, imagen, precio, categoria = '') {
  if (!seccionId) { mostrarNotificacion('Falta ID de sección', 'error'); return; }
  if (!precioValido(precio)) { mostrarNotificacion('No se puede agregar un producto sin precio', 'error'); return; }
  
  window.parent.postMessage({
    accion: 'agregarProducto',
    seccionId,
    padreId: padreIdTipologia || null,
    producto: {
      codigo, descripcion,
      imagen: validarImagenUrl(imagen) || '',
      precio: Number(precio),
      esTypologia: false,
      categoria: categoria || ''
    }
  }, '*');
  
  mostrarNotificacion('✓ ¡Producto agregado exitosamente!');
  
  const btn = event.target;
  const orig = btn.textContent;
  btn.textContent = '¡Agregado!';
  btn.style.background = 'var(--success)';
  btn.disabled = true;
  setTimeout(() => {
    btn.textContent = orig;
    btn.style.background = '';
    btn.disabled = false;
  }, 1500);
}

// ============================================================================
// INICIALIZACIÓN
// ============================================================================
document.addEventListener('DOMContentLoaded', async () => {
  console.log('🚀 Iniciando aplicación...');
  contentContainer.innerHTML = '<div class="loader">⏳ Cargando categorías...</div>';
  
  const categoriasOk = await cargarCategorias();
  if (!categoriasOk) console.warn('⚠️ No se pudieron cargar las categorías, continuando...');
  
  cargarListasPrecio();
  
  const padreInicial = urlParams.get('padre');
  if (padreInicial) navegarA(padreInicial, `Cat ${padreInicial}`);
  else cargarNivelPrincipal();
  
  console.log('✅ Aplicación iniciada');
});
</script>
</body>
</html>