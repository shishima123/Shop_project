<?php

namespace App\Http\Controllers;

use App\CommentRating;
use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Order;
use App\OrderItem;
use App\User;
use Exception;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::leftJoin(
            'comment_ratings',
            'users.id',
            '=',
            'comment_ratings.user_id'
        )->selectRaw('users.*,count(comment_ratings.user_id) as total_comment')->groupBy('users.id')->orderBy('role')->paginate(10);
        // return $users;
        return view('Admin.user.index', compact('users'));
    }

    public function edit($id)
    {
        $user = User::where('id', $id)->with('products')->first();
        return view('Admin.user.edit', compact('user'));
    }

    public function update(UserUpdateRequest $request, $id)
    {
        try {
            $user = User::findorfail($id);
            $user->name = $request->name;
            $user->phone = $request->phone;
            $user->address = $request->address;
            $user->save();
            return redirect()->route('user.index')->with(['flash_type' => 'success', 'flash_message' => 'Success!!! Complete Update User.']);
        } catch (Exception $e) {
            return redirect()->route('user.index')->with(['flash_type' => 'danger', 'flash_message' => 'Fail!!! Fail To Update User.']);
        }
    }

    public function store(UserRegisterRequest $request)
    {
        try {
            $user = new User;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->role = $request->role;
            $user->save();
            return redirect()->route('user.index')->with(['flash_type' => 'success', 'flash_message' => 'Success!!! Complete Add User.']);
        } catch (Exception $e) {
            return redirect()->route('user.index')->with(['flash_type' => 'danger', 'flash_message' => 'Fail!!! Fail To Add User.']);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $user = User::findorfail($id);
            CommentRating::where('user_id', $id)->delete();

            $getOrders = $user->orders()->get();
            foreach ($getOrders as $getOrder) {
                OrderItem::where('order_id', $getOrder->id)->delete();
            }
            Order::where('user_id', $id)->delete();
            User::findorfail($id)->delete();
            DB::commit();
            return redirect()->route('user.index')->with(['flash_type' => 'success', 'flash_message' => 'Success!!! Complete Delete User.']);
        } catch (Exception $e) {
            DB::rollback();
            return redirect()->route('user.index')->with(['flash_type' => 'danger', 'flash_message' => 'Fail!!! Fail To Add User.']);
        }
    }
}
