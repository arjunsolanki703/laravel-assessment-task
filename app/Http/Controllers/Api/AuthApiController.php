<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Mail;

class AuthApiController extends Controller
{
    public function inviteUser(Request $request){
        $request_data = $request->all();
        $validator =  Validator::make($request->all(),[
            'email' => 'required|string|email|max:255|unique:users'
        ]);
        
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 422);
        }
        try {
            $email =$request->email;
            $invitation = new Invitation($request->all());
            $token = $invitation->generateInvitationToken();
            $invitation->email = $request->get('email');
            $invitation->invitation_token = $token;
            $invitation->save();
            Mail::send('emails.invitation', ['email' => $email,'link'=>$invitation->getLink()], function ($message) use ($email)
            {
                $message->from('test@gmail.com', 'Arjun');
                $message->to($email);
            });
            return response()->json(['status'=>true,'message'=>'Invite user successfully','invitation_link'=>$invitation->getLink()],200);
        }  catch (\Exception $e) {
            
            return $e->getMessage();
        }
    }

    public function registeruser(Request $request){
        $invitation_token = $request->get('invitation_token');
        $invitation = Invitation::where('invitation_token', $invitation_token)->first();
        if(!$invitation){
            return response()->json(['status'=>false,'message'=>'Your link expire'],400);
        }
        $email = $invitation->email;
        $validator =  Validator::make($request->all(),[
            'user_name' => 'required|string|min:4|max:20|unique:users',
            'password' => 'required|string|min:6',
        ]);
        
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 422);
        }
        try {
            $user= User::create([
                'user_name' => $request->get('user_name'),
                'email' => $email,
                'password' => Hash::make($request->get('password')),
                'user_role' => 'user',
                'registered_at' => new \DateTime(),
                'verify_code' => $this->createCode()
            ]);
            $code = $user->verify_code;
            Mail::send('emails.verify', ['code' => $code], function ($message) use ($email)
            {
                $message->from('test@gmail.com', 'Arjun');
                $message->to($email);
            });
            $invitation->delete();
            return response()->json(['status'=>true,'message'=>'User  register successfully','code'=>$user->verify_code],201);
        }   catch (\Exception $e) {
        
            return $e->getMessage();
        }
    }

    public function createCode(){
        return random_int(100000, 999999);
    }

    public function confirmregisterUser(Request $request){
        $validator =  Validator::make($request->all(),[
            'email' => 'required|string|max:255',
            'verify_code' => 'required|string|min:6',
        ]);
        
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 422);
        }
        try{
            $user = User::where(['email'=>$request->get('email'),'verify_code'=>$request->get('verify_code')])->first();
            if($user){
                $user_data = User::find($user->id);
                $user_data->verify_at = 1;
                $user_data->save();
                return response()->json(['status'=>true,'message'=>'User  verify successfully'],200);
            } else {
                return response()->json(['status'=>false,'message'=>'User Not verify'],422);
            }
        }   catch (\Exception $e) {
        
            return $e->getMessage();
        }
    }

    public function login(Request $request){    
        $user_verify_check = User::where(['user_name' => $request->user_name])->first();
        if($user_verify_check->verify_at != 1){
            return response()->json(['status'=>false,'message'=>'Verify Your account first'],401);
        }
        $request->validate([
            'user_name' => 'required',
            'password' => 'required',
        ]);
        if(Auth::attempt(array('user_name' => $request->user_name, 'password' => $request->password)))
        {
            $user_login_token= auth()->user()->createToken('laravel-auth-profile-task')->accessToken;
           
            return response()->json(['status'=>true,'message'=>'User login successfully','token'=> $user_login_token],200);
        }
        else
        {
            return response()->json(['status'=>false,'message'=>'Unauthorized'],401);
        }
    }
    public function updateProfile(Request $request){
        $user_verify_check = User::where(['user_name' => $request->username])->first();
        if($user_verify_check->verify_at != 1){
            return response()->json(['status'=>false,'message'=>'Verify Your account first'],401);
        }
        $validator =  Validator::make($request->all(),[
            'name' => 'string|max:255',
            'avatar' => 'dimensions:max_width=256,max_height=256|mimes:jpg,png,jpeg',
            'password' => 'string|min:6',
            'email' => 'string|email|max:255|unique:users',
            'username' =>'required'
        ]);
        if($validator->fails()){
            return response()->json([
                "error" => 'validation_error',
                "message" => $validator->errors(),
            ], 422);
        }
        try{
            $user = User::where(['user_name'=>$request->get('username')])->first();
            if($user){
                if($request->file('avatar')) {
                    $file = $request->file('avatar');
                    $fileName = time().'_'.$file->getClientOriginalName();
                    $filePath = $request->file('avatar')->storeAs('uploads', $fileName, 'public');
                }
                $user = User::where(['user_name'=>$request->get('user_name')])
                ->update( 
                       array( 
                            "email" => $request->email ?: $user->email,
                            "password" => $request->password ? Hash::make($request->get('password')) : $user->password,
                            "avatar" =>$fileName ?: null,
                            'name' =>$request->name ?: $user->name
                        )
                       );
                return response()->json(['status'=>true,'message'=>'Update Profile Successfully'],200);
            } else {
                return response()->json(['status'=>false,'message'=>'Profile Not Update'],422);
            }
        }   catch (\Exception $e) {
        
            return $e->getMessage();
        }
    }
   public function logout()
    {
        if (Auth::check()) {
            Auth::user()->AauthAcessToken()->delete();
        }
        return response()->json(['status'=>true,'message'=>'Successfully logged out'],200);
    } 
}
