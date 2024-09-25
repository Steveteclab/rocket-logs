// Esperar a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', () => {
    // URL base de la API
    const API_BASE_URL = 'http://localhost:8000/api';
  
    // Obtener los elementos del DOM
    const loginForm = document.getElementById('login-form');
    const authMessage = document.getElementById('auth-message');
    const fetchEventsBtn = document.getElementById('fetch-events');
    const eventsList = document.getElementById('events-list');
    const fetchLogsBtn = document.getElementById('fetch-logs');
    const logsList = document.getElementById('logs-list');
  
    // Función para iniciar sesión
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const username = document.getElementById('username').value;
      const password = document.getElementById('password').value;
  
      try {
        const response = await fetch(`${API_BASE_URL}/login.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ username, password })
        });
  
        const result = await response.json();
        
        if (response.ok) {
          authMessage.textContent = 'Inicio de sesión exitoso';
          authMessage.style.color = 'green';
        } else {
          authMessage.textContent = result.error;
          authMessage.style.color = 'red';
        }
      } catch (error) {
        console.error('Error al iniciar sesión:', error);
        authMessage.textContent = 'Error al conectar con el servidor';
        authMessage.style.color = 'red';
      }
    });
  
    // Función para obtener eventos
    fetchEventsBtn.addEventListener('click', async () => {
    try {
        const response = await fetch(`${API_BASE_URL}/get_events.php`);
        const result = await response.json();
        
        eventsList.innerHTML = ''; // Limpiar la lista anterior
    
        if (response.ok) {
        result.forEach(event => {
            const li = document.createElement('li');
            li.textContent = `Evento: ${event.event_type} - Descripción: ${event.event_description}`;
            eventsList.appendChild(li);
        });
        } else {
        eventsList.innerHTML = '<li>Error al obtener eventos</li>';
        }
    } catch (error) {
        console.error('Error al obtener eventos:', error);
        eventsList.innerHTML = '<li>Error de conexión</li>';
    }
    });
  
    // Función para obtener logs de errores
    fetchLogsBtn.addEventListener('click', async () => {
      try {
        const response = await fetch(`${API_BASE_URL}/get-error-log.php`);
        const result = await response.json();
  
        logsList.innerHTML = ''; // Limpiar la lista anterior
  
        if (response.ok) {
          result.logs.forEach(log => {
            const li = document.createElement('li');
            li.textContent = `Error: ${log.error_message} - Nivel: ${log.error_level}`;
            logsList.appendChild(li);
          });
        } else {
          logsList.innerHTML = '<li>Error al obtener logs de errores</li>';
        }
      } catch (error) {
        console.error('Error al obtener logs:', error);
        logsList.innerHTML = '<li>Error de conexión</li>';
      }
    });
  });  