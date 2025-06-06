document.addEventListener('DOMContentLoaded', () => {
    const userTypeSelect = document.getElementById('user_type');
    const servicesField = document.getElementById('services-field');
    if (userTypeSelect && servicesField) {
        userTypeSelect.addEventListener('change', () => {
            servicesField.style.display = userTypeSelect.value === 'studio' ? 'block' : 'none';
        });
    }
});