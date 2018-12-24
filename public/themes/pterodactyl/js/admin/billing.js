let mL = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

let countryPie = new Chart($('#country_chart'), {
    type: 'pie',
    data: {
        labels: income_country_graph.map(function(item) {return item.billing_country;}),
        datasets: [
            {
                label: 'Income (in USD)',
                backgroundColor: income_country_graph.map(function(item) {return item.color;}),
                data: income_country_graph.map(function(item) {return item.amount;})
            }
        ]
    }
});

let monthPie = new Chart($('#month_chart'), {
    type: 'pie',
    data: {
        labels: income_month_graph.map(function(item) {return mL[item.month-1];}),
        datasets: [
            {
                label: 'Income (in USD)',
                backgroundColor: income_month_graph.map(function(item) {return item.color;}),
                data: income_month_graph.map(function(item) {return item.amount;})
            }
        ]
    }
});