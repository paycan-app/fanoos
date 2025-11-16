<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cross-Sell Product Pairs</title>
    <script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
</head>
<body>
    <h1>Top 10 Cross-Sell Product Pairs</h1>
    
    <div id="chart"></div>

    <script>
        // داده‌ها را از لاراول دریافت می‌کنیم
        var productCounts = @json($productCounts);
        
        // تبدیل داده‌ها برای استفاده در Plotly
        var labels = Object.keys(productCounts);
        var values = Object.values(productCounts);

        // تنظیمات نمودار با استفاده از Plotly
        var data = [{
            x: values,
            y: labels,
            type: 'bar',
            orientation: 'h',
            text: values,
            textposition: 'outside',
            marker: {
                color: values,
                colorscale: 'Viridis'
            }
        }];
        
        var layout = {
            title: 'Top 10 Cross-Sell Product Pairs',
            xaxis: {
                title: 'Number of Orders'
            },
            yaxis: {
                title: 'Product Pairs'
            }
        };

        // رسم نمودار
        Plotly.newPlot('chart', data, layout);
    </script>
</body>
</html>
