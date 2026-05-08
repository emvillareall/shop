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
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }

  .contenedor {
    position: relative;
    width: 200px;
    background: #fff;
    border: 1.5px dashed #000;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-top: 10px;
    padding: 8px 5px;
    box-sizing: border-box;
    overflow: hidden;
    background-image: url('{{ public_path("imagenes/logo.png") }}');
    background-repeat: no-repeat;
    background-position: center 10px;
    background-size: 200px auto;
  }

  h2 {
    font-size: 14px;
    text-align: center;
    color: #000;
    margin: 0 0 6px;
    font-weight: 700;
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
  color: #000;
  font-weight: bold;
  font-size: 12px;
}

.info-item span {
  color: #000;
  font-size: 12px;
  font-weight: normal; /* Solo contenido normal */
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

  <!-- Guía 1 -->
  <div class="contenedor">
    <h2>DESTINATARIO</h2>
    <div class="info">
      <div class="info-item"><strong>Nombre: </strong><span>{{$cliente->nombres_clientes}} {{$cliente->apellidos_clientes}}</span></div>
      <div class="info-item"><strong>Cédula: </strong><span>{{$cliente->cedula_clientes}}</span></div>
      <div class="info-item"><strong>Tel: </strong><span>{{$cliente->telefono_clientes}}</span></div>
      <div class="info-item"><strong>Ciudad: </strong><span>{{$cliente->ciudad_clientes}}</span></div>
      <div class="info-item"><strong>Direcc: </strong><span>{{$cliente->direccion_clientes}}</span></div>
    </div>
    <div class="divider"></div>
    <h2>REMITENTE</h2>
    <div class="info">
      <div class="info-item"><strong>Nombre: </strong><span>{{$tienda->nombres_dueno_tienda}} {{$tienda->apellidos_dueno_tienda}}</span></div>
      <div class="info-item"><strong>Cédula: </strong><span>{{$tienda->cedula_dueno_tienda}}</span></div>
      <div class="info-item"><strong>Tel: </strong><span>{{$tienda->telefono_dueno_tienda}}</span></div>
    </div>
  </div>

</body>
</html>
