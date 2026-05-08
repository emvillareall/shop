<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Guía Súper Compacta – PEDIDOS BOOTY</title>
  <style>
body {
  margin: 0;
  padding: 10px;
  background: #f8f9fa;
  font-family: DejaVu Sans, Arial, sans-serif;
  white-space: normal;
}

.contenedor {
  display: inline-block;
  vertical-align: top;
  width: 200px;
  background: #fff;
  border: 1.5px dashed #000;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  margin: 10px 6px 0 0; /* Espacio entre tarjetas */
  padding: 8px 5px;
  box-sizing: border-box;
  overflow: hidden;
  background-image: url('{{ public_path("imagenes/logo.png") }}');
  background-repeat: no-repeat;
  background-position: center 10px;
  background-size: 200px auto;
}
.fila-horizontal {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  gap: 12px;
  overflow-x: auto;
  width: 100%;
}


    h2 {
      font-size: 14px;
      text-align: center;
      color: #2C3E50;
      margin: 0 0 6px;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .info {
      margin-bottom: 10px;
    }

    .info-item {
      display: flex;
      margin-bottom: 4px;
    }

    .info-item strong {
      width: 65px;
      color: #34495E;
      font-weight: 600;
      font-size: 11px;
    }

    .info-item span {
      color: #2C3E50;
      font-size: 11px;
      line-height: 1.2;
    }

    .divider {
      height: 1px;
      background-color: #D5DBDB;
      margin: 8px 0;
    }
  </style>
</head>
<body>
  <div class="fila-horizontal">
    @foreach($pedidos as $pedido)
      <div class="contenedor">
        <h2>DESTINATARIO</h2>
        <div class="info">
          <div class="info-item"><strong>Nombre:</strong><span>{{ $pedido->nombres_clientes }} {{ $pedido->apellidos_clientes }}</span></div>
          <div class="info-item"><strong>Cédula:</strong><span>{{ $pedido->cedula_clientes }}</span></div>
          <div class="info-item"><strong>Tel:</strong><span>{{ $pedido->telefono_clientes }}</span></div>
          <div class="info-item"><strong>Ciudad:</strong><span>{{ $pedido->ciudad_clientes }}</span></div>
          <div class="info-item"><strong>Direcc:</strong><span>{{ $pedido->direccion_clientes }}</span></div>
        </div>
        <div class="divider"></div>
        <h2>REMITENTE</h2>
        <div class="info">
          <div class="info-item"><strong>Nombre:</strong><span>{{ $pedido->nombres_dueno_tienda }} {{ $pedido->apellidos_dueno_tienda }}</span></div>
          <div class="info-item"><strong>Cédula:</strong><span>{{ $pedido->cedula_dueno_tienda }}</span></div>
          <div class="info-item"><strong>Tel:</strong><span>{{ $pedido->telefono_dueno_tienda }}</span></div>
        </div>
      </div>
    @endforeach
  </div>
</body>
</html>			
