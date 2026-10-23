document.addEventListener('livewire:init', () => {

    Livewire.on('notify', async (data) => {

        const permission = await Notification.requestPermission();

        const payload = data[0]

        if (permission !== 'granted') {                        
            return;
        }
        const registration = await navigator.serviceWorker.ready;

        registration.showNotification(payload.title, {
            body: payload.body,
            icon: '/images/logo.png'
        });

    });

});
import './pwa-install';

