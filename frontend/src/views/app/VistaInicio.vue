<template>
  <PlantillaApp>
    <div class="dashboard-container">
      <!-- Header Section -->
      <header class="dashboard-header animate-fade-in">
        <div class="header-content">
          <h1 class="page-title">Vista General</h1>
          <p class="page-subtitle">Panel ejecutivo de {{ usuario?.name || 'Administrador' }} • {{ currentDate }}</p>
        </div>
        <div class="quick-stats-summary">
          <div class="summary-item">
            <span class="dot online"></span>
            <span class="label">Sistemas Activos</span>
          </div>
        </div>
      </header>

      <!-- Executive Report Stats -->
      <section class="executive-stats animate-slide-up">
        <div v-for="stat in executiveStats" :key="stat.label" class="exec-card">
          <div class="exec-card-icon" :style="{ backgroundColor: stat.bgColor, color: stat.color }">
            <component :is="stat.icon" width="20" height="20" />
          </div>
          <div class="exec-card-info">
            <span class="exec-label">{{ stat.label }}</span>
            <div class="exec-value-row">
              <span class="exec-value">{{ stat.value }}</span>
              <span class="exec-trend" :class="stat.trendType">
                {{ stat.trend }}
              </span>
            </div>
          </div>
        </div>
      </section>

      <!-- Quick Actions -->
      <section class="quick-actions-section animate-slide-up" style="animation-delay: 0.1s">
        <h3 class="section-title">Acciones Rápidas</h3>
        <div class="actions-grid">
          <button v-for="action in quickActions" :key="action.label" class="action-btn">
            <div class="action-icon">
              <component :is="action.icon" width="18" height="18" />
            </div>
            <span>{{ action.label }}</span>
          </button>
        </div>
      </section>

      <!-- Main ERP Modules (Minimalist Cards) -->
      <section class="modules-grid animate-slide-up" style="animation-delay: 0.2s">
        <div v-for="module in erpModules" :key="module.title" class="module-card">
          <div class="module-header">
            <div class="module-icon-box">
              <component :is="module.icon" width="24" height="24" />
            </div>
            <div class="module-meta">
              <h3>{{ module.title }}</h3>
              <p>{{ module.description }}</p>
            </div>
          </div>
          <div class="module-footer">
            <div class="module-stat">
              <span class="stat-num">{{ module.count }}</span>
              <span class="stat-text">Registrados</span>
            </div>
            <button class="module-link">
              Gestionar
              <ChevronRightIcon width="16" height="16" />
            </button>
          </div>
        </div>
      </section>

      <!-- Usage Analytics -->
      <section class="analytics-row animate-slide-up" style="animation-delay: 0.3s">
        <div class="card analytics-card">
          <div class="card-header">
            <div class="card-title-group">
              <h3>Uso de la Plataforma</h3>
              <p>Actividad de los últimos 7 días</p>
            </div>
            <div class="card-actions">
              <select class="minimal-select">
                <option>Esta semana</option>
                <option>Mes pasado</option>
              </select>
            </div>
          </div>
          <div class="chart-container">
            <div v-for="(bar, index) in dummyChartData" :key="index" class="chart-column">
              <div 
                class="chart-bar-fill" 
                :style="{ height: bar.height + '%' }"
                :class="{ 'active': bar.active }"
              >
                <div class="bar-tooltip">{{ bar.value }}</div>
              </div>
              <span class="chart-label">{{ bar.day }}</span>
            </div>
          </div>
        </div>
        
        <div class="card mini-activity-card">
          <div class="card-header">
            <h3>Notificaciones</h3>
          </div>
          <div class="mini-activity-list">
            <div v-for="item in recentActivity" :key="item.id" class="mini-activity-item">
              <div class="activity-icon-circle" :class="item.type">
                <component :is="item.icon" width="14" height="14" />
              </div>
              <div class="activity-content">
                <p class="activity-text"><strong>{{ item.user }}</strong> {{ item.action }}</p>
                <span class="activity-time">{{ item.time }}</span>
              </div>
            </div>
          </div>
          <button class="view-all-btn">Ver todas las notificaciones</button>
        </div>
      </section>
    </div>
  </PlantillaApp>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import PlantillaApp from '@/layouts/PlantillaApp.vue'
import { 
  UsersIcon, 
  ChartIcon, 
  FolderIcon, 
  DocumentIcon, 
  PlusIcon,
  ChevronRightIcon,
  BellIcon,
  SettingsIcon,
  SearchIcon
} from '@/components/globales/iconos'

const authStore = useAuthStore()
const usuario = computed(() => authStore.usuario)

const currentDate = computed(() => {
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
  return new Date().toLocaleDateString('es-ES', options)
})

// Executive stats data
const executiveStats = [
  { label: 'Uso Mensual', value: '94.2%', trend: '+2.4%', trendType: 'positive', icon: ChartIcon, bgColor: 'rgba(59, 130, 246, 0.1)', color: '#3b82f6' },
  { label: 'Clientes Nuevos', value: '128', trend: '+12%', trendType: 'positive', icon: UsersIcon, bgColor: 'rgba(16, 185, 129, 0.1)', color: '#10b981' },
  { label: 'Proyectos Activos', value: '14', trend: '-2', trendType: 'neutral', icon: FolderIcon, bgColor: 'rgba(245, 158, 11, 0.1)', color: '#f59e0b' },
  { label: 'Documentos', value: '1,240', trend: '+84', trendType: 'positive', icon: DocumentIcon, bgColor: 'rgba(139, 92, 246, 0.1)', color: '#8b5cf6' },
]

// Quick actions
const quickActions = [
  { label: 'Nuevo Producto', icon: PlusIcon },
  { label: 'Registrar Venta', icon: DocumentIcon },
  { label: 'Añadir Cliente', icon: UsersIcon },
  { label: 'Nueva Tarea', icon: FolderIcon },
  { label: 'Buscar Todo', icon: SearchIcon },
]

// ERP Modules dummy data
const erpModules = [
  { title: 'Productos', description: 'Inventario, categorías y stock.', count: '1,420', icon: FolderIcon },
  { title: 'Clientes', description: 'Base de datos y fidelización.', count: '850', icon: UsersIcon },
  { title: 'Proveedores', description: 'Contactos y órdenes de compra.', count: '45', icon: DocumentIcon },
  { title: 'Proyectos', description: 'Seguimiento de tareas y tiempos.', count: '12', icon: ChartIcon },
]

// Dummy Chart Data
const dummyChartData = [
  { day: 'Lun', height: 40, value: '2.4k', active: false },
  { day: 'Mar', height: 65, value: '3.8k', active: false },
  { day: 'Mie', height: 45, value: '2.9k', active: false },
  { day: 'Jue', height: 85, value: '5.1k', active: true },
  { day: 'Vie', height: 55, value: '3.2k', active: false },
  { day: 'Sab', height: 30, value: '1.8k', active: false },
  { day: 'Dom', height: 20, value: '1.2k', active: false },
]

// Recent Activity dummy data
const recentActivity = [
  { id: 1, user: 'Sistema', action: 'generó reporte mensual.', time: 'Hace 10 min', icon: DocumentIcon, type: 'info' },
  { id: 2, user: 'Admin', action: 'añadió nuevo producto.', time: 'Hace 1 hora', icon: PlusIcon, type: 'success' },
  { id: 3, user: 'Ventas', action: 'cerró proyecto X.', time: 'Hace 3 horas', icon: ChartIcon, type: 'warning' },
]
</script>

<style scoped>
.dashboard-container {
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Animations */
.animate-fade-in {
  animation: fadeIn 0.6s ease-out forwards;
}

.animate-slide-up {
  animation: slideUp 0.6s ease-out forwards;
  opacity: 0;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideUp {
  from { 
    opacity: 0;
    transform: translateY(20px);
  }
  to { 
    opacity: 1;
    transform: translateY(0);
  }
}

/* Header */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  border-bottom: 1px solid var(--color-border);
  padding-bottom: 1.5rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.025em;
  color: var(--color-heading);
  margin: 0;
}

.page-subtitle {
  color: var(--color-text-muted, #64748b);
  margin-top: 0.25rem;
  font-size: 0.95rem;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--color-surface-bg);
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  border: 1px solid var(--color-border);
  font-size: 0.8rem;
  font-weight: 600;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.dot.online {
  background: #10b981;
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
}

/* Executive Stats */
.executive-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.25rem;
}

.exec-card {
  background: var(--color-surface-bg);
  padding: 1.25rem;
  border-radius: 1.25rem;
  border: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.exec-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 20px -10px rgba(0, 0, 0, 0.1);
  border-color: var(--color-primary-light, #94a3b8);
}

.exec-card-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.exec-card-info {
  display: flex;
  flex-direction: column;
}

.exec-label {
  font-size: 0.85rem;
  color: var(--color-text-muted, #64748b);
  font-weight: 500;
}

.exec-value-row {
  display: flex;
  align-items: baseline;
  gap: 0.75rem;
}

.exec-value {
  font-size: 1.4rem;
  font-weight: 700;
  color: var(--color-heading);
}

.exec-trend {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
}

.exec-trend.positive { color: #10b981; background: rgba(16, 185, 129, 0.1); }
.exec-trend.neutral { color: #64748b; background: rgba(100, 116, 139, 0.1); }

/* Quick Actions */
.quick-actions-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.section-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--color-heading);
  margin: 0;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 1rem;
}

.action-btn {
  background: var(--color-surface-bg);
  border: 1px solid var(--color-border);
  padding: 0.85rem;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  font-weight: 500;
  font-size: 0.9rem;
  color: var(--color-text);
}

.action-btn:hover {
  background: var(--color-primary-light, #f8fafc);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.action-icon {
  width: 32px;
  height: 32px;
  background: rgba(0,0,0,0.03);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Modules Grid */
.modules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.module-card {
  background: var(--color-surface-bg);
  border: 1px solid var(--color-border);
  border-radius: 1.5rem;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  transition: all 0.3s;
}

.module-card:hover {
  border-color: var(--color-primary);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.module-header {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.module-icon-box {
  width: 54px;
  height: 54px;
  background: var(--color-primary-light, #eef2ff);
  color: var(--color-primary);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.module-meta h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
}

.module-meta p {
  margin: 0.25rem 0 0 0;
  font-size: 0.875rem;
  color: var(--color-text-muted, #64748b);
  line-height: 1.4;
}

.module-footer {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  padding-top: 1.25rem;
  border-top: 1px dashed var(--color-border);
}

.module-stat {
  display: flex;
  flex-direction: column;
}

.stat-num {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-heading);
}

.stat-text {
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-text-muted);
  font-weight: 600;
}

.module-link {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.75rem;
  background: transparent;
  border: none;
  color: var(--color-primary);
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: background 0.2s;
}

.module-link:hover {
  background: var(--color-primary-light, #eef2ff);
}

/* Analytics & Activity */
.analytics-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
}

@media (max-width: 1024px) {
  .analytics-row {
    grid-template-columns: 1fr;
  }
}

.card {
  background: var(--color-surface-bg);
  border-radius: 1.5rem;
  border: 1px solid var(--color-border);
  padding: 1.5rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.card-title-group h3 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
}

.card-title-group p {
  margin: 0.25rem 0 0 0;
  font-size: 0.85rem;
  color: var(--color-text-muted);
}

.minimal-select {
  padding: 0.4rem 0.8rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border);
  background: white;
  font-size: 0.85rem;
  font-weight: 500;
  outline: none;
}

.chart-container {
  height: 240px;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  padding: 0 0.5rem;
}

.chart-column {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  height: 100%;
  justify-content: flex-end;
}

.chart-bar-fill {
  width: 100%;
  max-width: 40px;
  background: var(--color-primary-light, #eef2ff);
  border-radius: 8px 8px 4px 4px;
  position: relative;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  cursor: pointer;
}

.chart-bar-fill:hover {
  background: var(--color-primary-hover, #c7d2fe);
}

.chart-bar-fill.active {
  background: var(--color-primary, #6366f1);
}

.bar-tooltip {
  position: absolute;
  top: -35px;
  left: 50%;
  transform: translateX(-50%);
  background: var(--color-heading);
  color: white;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  opacity: 0;
  transition: opacity 0.2s;
  pointer-events: none;
  white-space: nowrap;
}

.chart-bar-fill:hover .bar-tooltip {
  opacity: 1;
}

.chart-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-text-muted);
}

/* Mini Activity */
.mini-activity-card {
  display: flex;
  flex-direction: column;
}

.mini-activity-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  flex-grow: 1;
}

.mini-activity-item {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.activity-icon-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.activity-icon-circle.info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.activity-icon-circle.success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.activity-icon-circle.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

.activity-content {
  display: flex;
  flex-direction: column;
}

.activity-text {
  margin: 0;
  font-size: 0.875rem;
  color: var(--color-text);
  line-height: 1.4;
}

.activity-time {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-top: 0.15rem;
}

.view-all-btn {
  margin-top: 1.5rem;
  padding: 0.75rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border);
  background: transparent;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text);
  cursor: pointer;
  transition: all 0.2s;
}

.view-all-btn:hover {
  background: var(--color-surface-pale, #f1f5f9);
  border-color: var(--color-text-muted);
}

/* Dark Mode Adjustments */
html.dark .minimal-select {
  background: var(--color-surface-bg);
  color: var(--color-text);
}

html.dark .action-icon {
  background: rgba(255,255,255,0.05);
}

html.dark .chart-bar-fill {
  background: rgba(99, 102, 241, 0.1);
}

html.dark .exec-card:hover,
html.dark .module-card:hover {
  background: rgba(255, 255, 255, 0.02);
}
</style>
