<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    //
    public function all(Request $request){


        $id = $request->input('id');
        $limit = $request->input('limmit',2);
        $status = $request->input('status');
    
        if($id){
            $transaction = Transaction::with(['items','Product'])->find($id);
    
            if ($transaction){
                return ResponseFormatter::success(
                    $transaction,'Data Transaksi Berhasil Diambil'
                );
            }else{
                return ResponseFormatter::error(
                    null,'Data Transaksi Tidak Ada',404
                );
            }
        }
    
        $transaction = Transaction::with(['items','Product'])->where('user_id', Auth::user()->id);
    
        if ($status){
            $transaction->where('status',$status);
        }
    
        return ResponseFormatter::success(
          $transaction->paginate($limit),
          'Data list transaksi berhasil diambil'  
        );

    }

    public function checkout(Request $request){
        $request->validate([
            'items'=>'required|array',
            'items.*.id' =>'exists:products,id',
            'total_price'=>'required',
            'shipping_price'=>'required',
            'status'=>'required|in:PENDING,SUCCESS,CANCELLED,FAILED,SHIPPING,SHIPPED',
        ]);

        $transaction = Transaction::create([
            'users_id'=> Auth::user()->id,
            'address' => $request->address,
            'total_prices' => $request->total_price,
            'shiping_prices' => $request->shipping_price,
            'status' => $request->status,
        ]);

        foreach($request->items as $product){
            TransactionItem::create([
                'users_id'=> Auth::user()->id,
                'products_id'=>$product['id'],
                'transactions_id'=>$transaction->id,
                'quantity' => $product['quantity']
            ]);
        }

        return ResponseFormatter::success($transaction->load('items.product'),'Transaksi berhasil');
    }
}
