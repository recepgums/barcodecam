<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Müşteri Adı</th>
            <th>Sipariş Tarihi</th>
            <th>Sipariş Durumu</th>
            <th>Sipariş Toplam Tutarı</th>
            <th>Kargo Firması</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>{{$order->customer_name}}</td>
            <td>{{$order->order_date}}</td>
            <td>{{$order->status}}</td>
            <td>{{$order->total_price}}</td>
            <td>{{$order->cargo_service_provider}}</td>
        </tr>
        </tbody>
    </table>
</div>


<br><br><br><br>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Resim</th>
            <th>Adet</th>
            <th>Ürün Adı</th>
        </tr>
        </thead>
        <tbody>
        @foreach($products as $product)
            <tr>
                <td>
                    <img src="{{\App\Helpers\TrendyolHelper::getProductByBarcode(auth()->user(),$product->barcode)->images[0]->url}}" height="50px" width="50px">
                </td>
                <td>{{$product->quantity}}</td>
                <td>{{$product->productName}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
