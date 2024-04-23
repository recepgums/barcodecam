@if($products && count($products) > 0)
    <div class="table-responsive card">
        <table class="table table-striped">
            <thead>
            <tr>
                <th style="text-align: center"></th>
                <th style="text-align: center">Adet</th>
                <th style="text-align: center">Ürün Adı</th>
            </tr>
            </thead>
            <tbody>
            @foreach($products as $product)
                <tr>
                    <td style="width: 33.33%;text-align: center">
                        <img style="width: 40%; height: 100px;"
                             src="{{\App\Helpers\TrendyolHelper::getProductByBarcode(auth()->user(),$product->barcode)->images[0]->url}}">
                    </td>
                    <td style="width: 20%; padding-top: 44px; text-align: center; vertical-align: middle;color: red">
                        <h1 style="margin: 0;">
                            <b>X 1</b>
                        </h1>
                    </td>
                    <td style="width: 40%;">
                        {{$product?->productName}}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@if($order)
    <br><br>

    <div class="table-responsive ">
        <table class="table table-striped ">
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
                <td>{{$order?->customer_name}}</td>
                <td>{{$order?->order_date}}</td>
                <td>{{$order?->status}}</td>
                <td>{{$order?->total_price}}</td>
                <td>{{$order?->cargo_service_provider}}</td>
            </tr>
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-danger" role="alert">
        Sipariş bulunamadı! Kargo numarasını doğru girdiğinizden ve siparişin kargolanmayı bekleyen bir sipariş
        olduğundan emin olun
    </div>
@endif
