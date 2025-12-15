# 📦 ND - Gestión de Productos No Enviables

**Plugin de validación logística para WooCommerce.**
##Versión: 1.5 Autor: Daniel Díaz Desarrollado exclusivamente para: Ahumadores Chaquiro

Este plugin añade una capa de validación en el checkout de WooCommerce para restringir la venta de ciertos productos basándose en la ciudad de facturación del cliente. Su función principal es **eliminar automáticamente** productos específicos del carrito si la ciudad no tiene cobertura y **restaurarlos** si el cliente cambia a una ciudad válida.

## 📋 Características Principales

* **🏙️ Validación en Tiempo Real:** Detecta el cambio de ciudad en el formulario de checkout mediante AJAX (`woocommerce_checkout_update_order_review`).
* **🚫 Eliminación Automática:** Si la ciudad ingresada no está en la lista blanca, elimina los productos de la categoría restringida.
* **🔄 Restauración Inteligente:** Si el usuario corrige la ciudad a una con cobertura, el plugin recupera los productos eliminados y los devuelve al carrito automáticamente.
* **⚠️ Feedback al Usuario:** Muestra un aviso visual en rojo dentro de la tabla de revisión del pedido, indicando qué productos fueron removidos por falta de cobertura.
* **💾 Gestión de Sesión:** Utiliza `WC()->session` para recordar temporalmente qué productos se quitaron para poder restaurarlos si es necesario.

## ⚙️ Configuración (Hardcoded)

Este plugin no tiene panel de administración. La configuración se realiza directamente en el código fuente (`nd-non-shippable-handler.php`).

### 1. Definir la Categoría Restringida
Busca la función `nd_get_target_category_slug()` y cambia el *slug* por el de la categoría que deseas limitar.

```php
function nd_get_target_category_slug() {
    // Cambiar 'combo-que-empiece-la-parranda' por tu slug
    return 'combo-que-empiece-la-parranda'; 
}
