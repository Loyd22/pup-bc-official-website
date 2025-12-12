/**
 * Enrollment Charts using Chart.js
 * Converts CSV data into interactive charts
 */

// PUP Biñan color scheme
const PUP_COLORS = {
    maroon: '#7a0019',
    maroonDark: '#540013',
    maroonLight: '#9a0023',
    gold: '#f3b233',
    goldDark: '#d79c2d',
    sand: '#faf5ef',
    ink: '#1f2328',
    muted: '#6b7280'
};

/**
 * Initialize all enrollment charts
 * @param {number} csvId - The CSV upload ID
 * @param {string} apiUrl - Optional API URL (if not provided, will be determined automatically)
 */
async function initEnrollmentCharts(csvId, apiUrl = null) {
    console.log('initEnrollmentCharts called with CSV ID:', csvId);
    
    try {
        // Use provided API URL or determine it
        let apiPath;
        if (apiUrl) {
            apiPath = `${apiUrl}?id=${csvId}`;
        } else if (window.CSV_API_URL) {
            apiPath = `${window.CSV_API_URL}?id=${csvId}`;
        } else {
            // Fallback: determine path from current location
            const currentPath = window.location.pathname;
            let basePath = '';
            if (currentPath.includes('/reports/')) {
                // Reports are in reports/ subdirectory, go up one level to reach pupbc-website root
                basePath = '../';
            } else if (currentPath.includes('/admin/')) {
                basePath = '../';
            } else if (currentPath.includes('/pages/')) {
                basePath = '../';
            } else {
                basePath = '';
            }
            apiPath = `${basePath}api/csv_data.php?id=${csvId}`;
        }
        
        console.log('Current pathname:', window.location.pathname);
        console.log('Fetching data from:', apiPath);
        const response = await fetch(apiPath);
        if (!response.ok) {
            const errorText = await response.text();
            console.error('API Error Response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('API Response:', result);
        
        if (result.status !== 'success') {
            throw new Error(result.error || 'Failed to load data');
        }
        
        if (!result.data || result.data.length === 0) {
            throw new Error('No data found in CSV file');
        }
        
        const { data, stats } = result;
        
        // Find course column
        const courseCol = findColumn(data[0], ['course', 'coursename', 'program']);
        if (!courseCol) {
            throw new Error('Course column not found');
        }
        
        // Find total_all column
        const totalAllCol = findColumn(data[0], ['total_all', 'totalall', 'total']);
        if (!totalAllCol) {
            throw new Error('Total enrollment column not found');
        }
        
        // Find gender columns
        const maleCol = findColumn(data[0], ['total_male', 'totalmale', 'male']);
        const femaleCol = findColumn(data[0], ['total_female', 'totalfemale', 'female']);
        
        // Find year columns
        const yearCols = Object.keys(data[0]).filter(col => 
            /^y\d+_total$/i.test(col) || /^year\d+$/i.test(col)
        ).sort();
        
        // Prepare data
        const courses = data.map(row => row[courseCol] || 'Unknown');
        const totals = data.map(row => parseFloat(row[totalAllCol] || 0));
        const males = maleCol ? data.map(row => parseFloat(row[maleCol] || 0)) : null;
        const females = femaleCol ? data.map(row => parseFloat(row[femaleCol] || 0)) : null;
        
        // Sort by total enrollment (descending)
        const sortedIndices = totals.map((val, idx) => ({ val, idx }))
            .sort((a, b) => b.val - a.val)
            .map(item => item.idx);
        
        // Create all charts
        createEnrollmentByCourseChart(courses, totals, sortedIndices);
        
        if (yearCols.length > 0) {
            createYearBreakdownChart(data, courseCol, yearCols, sortedIndices);
            createEnrollmentByYearChart(data, yearCols);
        }
        
        if (males && females) {
            createGenderDistributionChart(stats.total_male, stats.total_female);
            createGenderByCourseChart(data, courseCol, maleCol, femaleCol, sortedIndices);
        }
        
        createTopCoursesChart(courses, totals, sortedIndices);
        
        console.log('All charts initialized successfully');
        
    } catch (error) {
        console.error('Error initializing charts:', error);
        const container = document.getElementById('charts-container') || document.querySelector('.charts-grid');
        if (container) {
            container.insertAdjacentHTML('beforeend', 
                `<div class="error-message" style="padding: 20px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c00; margin: 20px;">
                    <strong>Error loading charts:</strong> ${error.message}<br>
                    <small>Check browser console for more details.</small>
                </div>`
            );
        } else {
            alert('Error loading charts: ' + error.message);
        }
    }
}

/**
 * Find column name (case-insensitive)
 */
function findColumn(row, possibleNames) {
    const keys = Object.keys(row);
    for (const name of possibleNames) {
        const found = keys.find(k => k.toLowerCase() === name.toLowerCase());
        if (found) return found;
    }
    return null;
}

/**
 * Chart 1: Enrollment by Course (Bar Chart)
 */
function createEnrollmentByCourseChart(courses, totals, sortedIndices) {
    const ctx = document.getElementById('chartEnrollmentByCourse');
    if (!ctx) {
        console.warn('Canvas element chartEnrollmentByCourse not found');
        return;
    }
    console.log('Creating enrollment by course chart');
    
    const sortedCourses = sortedIndices.map(i => courses[i]);
    const sortedTotals = sortedIndices.map(i => totals[i]);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: sortedCourses,
            datasets: [{
                label: 'Total Enrollment',
                data: sortedTotals,
                backgroundColor: PUP_COLORS.maroon,
                borderColor: '#ffffff',
                borderWidth: 1.5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Total Enrollment by Course',
                    font: { size: 15, weight: 'bold' },
                    color: PUP_COLORS.maroon,
                    padding: { bottom: 20 }
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Enrollment: ${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)',
                        lineWidth: 1,
                        drawBorder: false
                    }
                }
            }
        }
    });
}

/**
 * Chart 2: Year-wise Enrollment Breakdown (Stacked Bar Chart)
 */
function createYearBreakdownChart(data, courseCol, yearCols, sortedIndices) {
    const ctx = document.getElementById('chartYearBreakdown');
    if (!ctx) return;
    
    // Get top 10 courses
    const top10Indices = sortedIndices.slice(0, 10);
    const top10Courses = top10Indices.map(i => data[i][courseCol] || 'Unknown');
    
    const colors = [
        PUP_COLORS.maroon,
        PUP_COLORS.maroonLight,
        PUP_COLORS.gold,
        PUP_COLORS.goldDark,
        PUP_COLORS.maroonDark
    ];
    
    const datasets = yearCols.map((col, idx) => {
        const yearLabel = col.replace('_total', '').replace('year', '').toUpperCase();
        return {
            label: `Year ${yearLabel}`,
            data: top10Indices.map(i => parseFloat(data[i][col] || 0)),
            backgroundColor: colors[idx % colors.length],
            borderColor: '#ffffff',
            borderWidth: 1
        };
    });
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: top10Courses,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Year-wise Enrollment Breakdown by Course',
                    font: { size: 15, weight: 'bold' },
                    color: PUP_COLORS.maroon,
                    padding: { bottom: 20 }
                },
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        padding: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: { display: false }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)',
                        lineWidth: 1,
                        drawBorder: false
                    }
                }
            }
        }
    });
}

/**
 * Chart 3: Gender Distribution (Pie Chart)
 */
function createGenderDistributionChart(totalMale, totalFemale) {
    const ctx = document.getElementById('chartGenderDistribution');
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Male', 'Female'],
            datasets: [{
                data: [totalMale, totalFemale],
                backgroundColor: [PUP_COLORS.maroon, PUP_COLORS.gold],
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Overall Gender Distribution',
                    font: { size: 15, weight: 'bold' },
                    color: PUP_COLORS.maroon,
                    padding: { bottom: 20 }
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: { size: 12, weight: 'bold' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Chart 4: Gender Comparison by Course (Grouped Bar Chart)
 */
function createGenderByCourseChart(data, courseCol, maleCol, femaleCol, sortedIndices) {
    const ctx = document.getElementById('chartGenderByCourse');
    if (!ctx) return;
    
    // Get top 10 courses
    const top10Indices = sortedIndices.slice(0, 10);
    const top10Courses = top10Indices.map(i => data[i][courseCol] || 'Unknown');
    const maleData = top10Indices.map(i => parseFloat(data[i][maleCol] || 0));
    const femaleData = top10Indices.map(i => parseFloat(data[i][femaleCol] || 0));
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: top10Courses,
            datasets: [
                {
                    label: 'Male',
                    data: maleData,
                    backgroundColor: PUP_COLORS.maroon,
                    borderColor: '#ffffff',
                    borderWidth: 1
                },
                {
                    label: 'Female',
                    data: femaleData,
                    backgroundColor: PUP_COLORS.gold,
                    borderColor: '#ffffff',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Gender Comparison by Course (Top 10)',
                    font: { size: 15, weight: 'bold' },
                    color: PUP_COLORS.maroon,
                    padding: { bottom: 20 }
                },
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        padding: 10
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    },
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)',
                        lineWidth: 1,
                        drawBorder: false
                    }
                }
            }
        }
    });
}

/**
 * Chart 5: Total Enrollment by Year (Line Chart)
 */
function createEnrollmentByYearChart(data, yearCols) {
    const ctx = document.getElementById('chartEnrollmentByYear');
    if (!ctx) return;
    
    const yearLabels = yearCols.map(col => 
        col.replace('_total', '').replace('year', '').toUpperCase()
    );
    
    const yearTotals = yearCols.map(col => {
        return data.reduce((sum, row) => {
            return sum + parseFloat(row[col] || 0);
        }, 0);
    });
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: yearLabels,
            datasets: [{
                label: 'Total Enrollment',
                data: yearTotals,
                borderColor: PUP_COLORS.maroon,
                backgroundColor: PUP_COLORS.maroon + '33', // Add transparency
                borderWidth: 3,
                pointBackgroundColor: PUP_COLORS.gold,
                pointBorderColor: PUP_COLORS.maroon,
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Total Enrollment by Year Level',
                    font: { size: 15, weight: 'bold' },
                    color: PUP_COLORS.maroon,
                    padding: { bottom: 20 }
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Enrollment: ${context.parsed.y.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)',
                        lineWidth: 1,
                        drawBorder: false
                    }
                }
            }
        }
    });
}

/**
 * Chart 6: Top 10 Courses (Horizontal Bar Chart)
 */
function createTopCoursesChart(courses, totals, sortedIndices) {
    const ctx = document.getElementById('chartTopCourses');
    if (!ctx) return;
    
    // Get top 10
    const top10Indices = sortedIndices.slice(0, 10);
    const top10Courses = top10Indices.map(i => courses[i]);
    const top10Totals = top10Indices.map(i => totals[i]);
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: top10Courses,
            datasets: [{
                label: 'Total Enrollment',
                data: top10Totals,
                backgroundColor: PUP_COLORS.maroon,
                borderColor: '#ffffff',
                borderWidth: 1.5
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Top 10 Courses by Enrollment',
                    font: { size: 15, weight: 'bold' },
                    color: PUP_COLORS.maroon,
                    padding: { bottom: 20 }
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Enrollment: ${context.parsed.x.toLocaleString()}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.1)',
                        lineWidth: 1,
                        drawBorder: false
                    }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });
}

