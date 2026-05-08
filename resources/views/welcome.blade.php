@extends('layouts.app')

@section('content')
<div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">

                <div class="card">
                    <div class="card-header">INCRITOS

                    </div>
                    <div class="card-body">
                        <div id="container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script type="text/javascript">

// Data retrieved from https://www.vikjavev.no/ver/snjomengd

Highcharts.chart('container', {
    chart: {
        type: 'spline'
    },
    title: {
        text: 'Bautismos, Matrimonios, Confirmaciones',
        align: 'left'
    },
    subtitle: {
        text: 'total de inscritos por fecha',
        align: 'left'
    },
    xAxis: {
        type: 'datetime',
        dateTimeLabelFormats: {
            // don't display the year
            month: '%e. %b',
            year: '%b'
        },
        title: {
            text: 'Date'
        }
    },
    yAxis: {
        title: {
            text: 'Cantidad de inscritos'
        },
        min: 0
    },
    tooltip: {
        headerFormat: '<b>{series.name}</b><br>',
        pointFormat: '{point.x:%e. %b}: {point.y:.0f}'
    },

    plotOptions: {
        series: {
            marker: {
                symbol: 'circle',
                fillColor: '#FFFFFF',
                enabled: true,
                radius: 2.5,
                lineWidth: 1,
                lineColor: null
            }
        }
    },

    colors: ['#6CF', '#39F', '#06C', '#036', '#000'],

    // Define the data points. All series have a year of 1970/71 in order
    // to be compared on the same x axis. Note that in JavaScript, months start
    // at 0 for January, 1 for February etc.
    series: [
        {
            name: 'Bautismos <?= $year?>',
            data: <?= $data_bautismos; ?>
        },
        {
            name: 'Bodas <?= $year?>',
            data: <?= $data_bodas; ?>
        },
        {
            name: 'Confirmaciones <?= $year?>',
            data: <?= $data_confirmacion; ?>
        }
    ]
});


/*  Highcharts.chart('container', {

    title: {
        text: 'Grafico por numero de Bautizados, odas, Confirmaciones',
        align: 'left'
    },

    subtitle: {
        text: 'DIÓSESIS DE SANTO DOMINGO DE LOS TSÁCHILAS PARROQUIA SAN ANTONIO DE PADUA COOP. RUMIÑAHUI',
        align: 'left'
    },

    yAxis: {
        title: {
            text: 'Numero de Bautisados'
        }
    },

    xAxis: {
        accessibility: {
            rangeDescription: 'Range: 2023 to 2024'
        }
    },

    legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'middle'
    },

    plotOptions: {
        series: {
            label: {
                connectorAllowed: false
            },
            pointStart: 2023
        }
    },

    series: [{
        name: 'Bautismos',
        data: $data //esto debe estar entre insercion de php
    }],

    responsive: {
        rules: [{
            condition: {
                maxWidth: 500
            },
            chartOptions: {
                legend: {
                    layout: 'horizontal',
                    align: 'center',
                    verticalAlign: 'bottom'
                }
            }
        }]
    }

});*/
</script>
@endsection