@if($products && count($products) > 0)
    <div class="container mt-4">
        <div class="row">
            @foreach($products as $product)
                <div class="col-md-3 mb-1 px-1">
                    <div class="card h-100 shadow rounded">
                        <img src="{{ \App\Helpers\TrendyolHelper::getProductByBarcode(auth()->user(), $product->barcode)->images[0]->url }}" class="card-img-top rounded-top product-image" alt="Product Image">
                        <div class="card-body text-center">
                            <h5 class="card-title">
                                {{ Str::limit($product->productName, 40) }}
                            </h5>
                            <p class="card-text text-danger">
                                <b>X {{$product->quantity}}</b>
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
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
<style>
    .product-image {
        height: 250px; /* Set a fixed height for the images */
        object-fit: cover; /* Ensure the images cover the area without distortion */
    }
</style>
