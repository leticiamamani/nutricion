// Funciones generales del sistema
document.addEventListener('DOMContentLoaded', function() {
    
    // Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            mainContent.classList.toggle('full-width');
            
            // Cambiar ícono
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });
    }
    
    // Auto-cerrar sidebar en móviles al hacer clic en un link
    if (window.innerWidth <= 768) {
        const sidebarLinks = document.querySelectorAll('#sidebar a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                sidebar.classList.add('active');
                mainContent.classList.add('full-width');
                const icon = sidebarCollapse.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            });
        });
    }
    
    // Auto-calculo de IMC
    const pesoInput = document.getElementById('peso_kg');
    const tallaInput = document.getElementById('talla_m');
    const imcInput = document.getElementById('imc');
    
    if (pesoInput && tallaInput && imcInput) {
        function calcularIMC() {
            const peso = parseFloat(pesoInput.value) || 0;
            const talla = parseFloat(tallaInput.value) || 0;
            
            if (peso > 0 && talla > 0) {
                const imc = peso / (talla * talla);
                imcInput.value = imc.toFixed(2);
                
                // Color según IMC
                const imcValue = parseFloat(imc.toFixed(2));
                if (imcValue < 18.5) {
                    imcInput.className = 'form-control bg-warning bg-opacity-25';
                } else if (imcValue >= 18.5 && imcValue <= 24.9) {
                    imcInput.className = 'form-control bg-success bg-opacity-25';
                } else if (imcValue >= 25 && imcValue <= 29.9) {
                    imcInput.className = 'form-control bg-warning bg-opacity-25';
                } else {
                    imcInput.className = 'form-control bg-danger bg-opacity-25';
                }
            } else {
                imcInput.className = 'form-control';
            }
        }
        
        pesoInput.addEventListener('input', calcularIMC);
        tallaInput.addEventListener('input', calcularIMC);
    }
    
    // Confirmación para eliminaciones
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este registro?')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-ocultar alertas
    const alertMessages = document.querySelectorAll('.alert');
    alertMessages.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }, 5000);
    });
    
    // Tooltips de Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Función para formatear números
function formatNumber(input) {
    if (!input) return '';
    return new Intl.NumberFormat('es-AR').format(input);
}

// Función para formatear fechas
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-AR');
}

/**
 * JavaScript para el nuevo menú con funcionalidad hamburguesa
 */

document.addEventListener('DOMContentLoaded', function() {
    // Control del menú hamburguesa para móviles
    const menuToggle = document.getElementById('menuToggle');
    const menuVertical = document.getElementById('menu-vertical');
    
    if (menuToggle && menuVertical) {
        menuToggle.addEventListener('click', function() {
            menuVertical.classList.toggle('menu-open');
            menuToggle.classList.toggle('active');
        });
    }
    
    // Cerrar menú al hacer clic en un enlace (en móviles)
    const menuLinks = document.querySelectorAll('.new-menu-link');
    menuLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                menuVertical.classList.remove('menu-open');
                menuToggle.classList.remove('active');
            }
        });
    });
    
    // Marcar enlace activo basado en la URL actual
    function setActiveMenu() {
        const currentPage = window.location.pathname.split('/').pop();
        const menuLinks = document.querySelectorAll('.new-menu-link');
        
        menuLinks.forEach(function(link) {
            const linkHref = link.getAttribute('href');
            if (linkHref && linkHref.includes(currentPage)) {
                link.classList.add('active');
            }
        });
    }
    
    setActiveMenu();
    
    // Efectos hover mejorados para tarjetas
    const cards = document.querySelectorAll('.card');
    cards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Actualizar la hora del último acceso
    function updateLastAccess() {
        const lastAccessElement = document.querySelector('.info-value:last-child');
        if (lastAccessElement) {
            const now = new Date();
            const formattedTime = now.toLocaleString('es-AR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            lastAccessElement.textContent = formattedTime;
        }
    }
    
    // Actualizar cada minuto
    setInterval(updateLastAccess, 60000);
    
    // Cerrar menú al hacer clic fuera de él (en móviles)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && menuVertical && menuToggle) {
            if (!menuVertical.contains(e.target) && !menuToggle.contains(e.target)) {
                menuVertical.classList.remove('menu-open');
                menuToggle.classList.remove('active');
            }
        }
    });
    
    // Prevenir el cierre del menú al hacer clic dentro de él
    if (menuVertical) {
        menuVertical.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});

/**
 * Función para formatear números grandes
 */
function formatNumber(num) {
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toString();
}

/**
 * Mostrar notificación temporal
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">×</button>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border: 1px solid #e0e0e0;
        border-left: 4px solid #000000;
        padding: 15px 20px;
        border-radius: 5px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Estilos CSS para las notificaciones
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .notification button {
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: #666;
    }
    
    .notification button:hover {
        color: #000000;
    }
`;
document.head.appendChild(notificationStyles);