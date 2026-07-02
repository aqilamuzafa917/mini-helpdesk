// import './bootstrap';
import Chart from 'chart.js/auto';

// Ikat Chart ke window agar bisa diakses global oleh Alpine/Browser
window.Chart = Chart;

console.log('Chart.js initialized globally');