<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Controller;
use App\Models\services\Commission;
use App\Models\services\ICICIAEPSTransaction;
use App\Models\services\PayoutBankResponse;
use App\Models\services\Wallet;
use App\Models\services\WalletSettelment;
use App\Models\User;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    /*
     * Check user wallet balance
     * */
    public function checkBalance(Request $request)
    {
        try {
            $wallet = Wallet::where('user_id', '=', Auth::user()->id)
                ->limit(1)
                ->orderby('id', 'DESC')
                ->get();
            if (count($wallet) > 0) {
                return response()->json(['response' => true, 'message' => "Balance Fetched", 'data' => ['balance' => $wallet[0]->closing_balance]]);
            } else {
                return response()->json(['response' => false, 'message' => "Balance Fetched", 'data' => ['balance' => 0]]);
            }


        } catch (\Exception $exception) {
            return response()->json(['response' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function walletTransaction(Request $request)
    {
        try {
            $input = json_decode($request->getContent(), true);
            $transaction = Wallet::select('user_wallet.*','users.fname',
                'users.mname','users.lname','users.contact','users.role')
                ->join('users','user_wallet.user_id','=','users.id')
                ->orderBy('id', 'DESC');
            if(in_array(Auth::user()->role,config('constants.admin'))){
                $data=$transaction
                    ->paginate();
            }else{
                $data=$transaction->where('user_id', Auth::user()->id)
                    ->simplePaginate();
            }
            return response()->json(['response' => true, 'message' => 'Record fetched', 'data' => $data]);
        } catch (\Exception $exception) {
            return response()->json(['response' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function walletOperationaeps($transactionData)
    {
        try {
            //get user transaction details
            $allowed_txnType = array('CW', 'AP');
            if (in_array($transactionData->txnType, $allowed_txnType)
                && $transactionData->status == "SUCCESS"
                && $transactionData->isWalletUpdate == false
            ) {
                $wallet = Wallet::where('user_id', '=', $transactionData->userId)
                    ->limit(1)
                    ->orderBy('id', 'DESC')
                    ->get();
                if (count($wallet) == 1) {
                    $previous_balance = $wallet[0]->closing_balance;
                } else {
                    $previous_balance = 0.00;
                }
                logger($wallet);
                $transaction_type = "Aeps";
                $description = "ICICI Aeps Cash Withdrawal";
                $wallet_operation = "cr";
                if ($transactionData->txnType == "AP") {
                    $transaction_type = "Aadhaar Pay";
                    $description = "ICICI Aadhaar Pay Cash Withdrawal";
                    $wallet_operation = "cr";
                }
                $walletData = array(
                    'user_id' => $transactionData->userId,
                    'service_id' => $transactionData->serviceId,
                    'transaction_type' => $transaction_type,
                    'transaction_reference' => $transactionData->id,
                    'description' => $description,
                    'transaction_date' => $transactionData->created_at,
                    'status' => "success",
                    'wallet_operation' => $wallet_operation,
                    'previous_balance' => $previous_balance,
                    'transacting_amount' => $transactionData->amount,
                );

                $walletStore = $this->store($walletData);
                logger($walletStore);
                if ($walletStore['response']) {
                    return $walletStore['data'];
                } else {
                    return false;
                }
            } else {
                return false;
                //return response()->json(['response'=>false,'message'=>'Operation execute if transaction is CW']);
            }
        } catch (\Exception $exception) {
            return response()->json(['response' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function store(array $data)
    {
        try {
            $validator = Validator::make($data, [
                'user_id' => 'required|integer',
                'service_id' => 'required|integer',
                'transaction_type' => 'required|string',
                'transaction_reference' => 'nullable|integer',
                'description' => 'required|string',
                'transaction_date' => 'required|date',
                'previous_balance' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'transacting_amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'wallet_operation' => 'required|string',
            ]);
            if ($validator->fails()) {
                return ['response' => false, 'message' => $validator->errors()];
            }
            $walletUpdate = new Wallet();
            $walletUpdate->user_id = $data['user_id'];
            $walletUpdate->service_id = $data['service_id'];
            $walletUpdate->transaction_type = $data['transaction_type'];
            $walletUpdate->transaction_reference = isset($data['transaction_reference']) ? $data['transaction_reference'] : null;
            $walletUpdate->description = $data['description'];
            $walletUpdate->transaction_date = $data['transaction_date'];
            $walletUpdate->status = "success";
            $walletUpdate->previous_balance = $data['previous_balance'];
            $walletUpdate->transacting_amount = $data['transacting_amount'];
            $walletUpdate->wallet_operation = $data['wallet_operation'];
            if ($data['wallet_operation'] == "cr") {
                $walletUpdate->closing_balance = $data['previous_balance'] + $data['transacting_amount'];
            }
            if ($data['wallet_operation'] == "dr") {
                $walletUpdate->closing_balance = $data['previous_balance'] - $data['transacting_amount'];
            }
            $walletUpdate->save();
            return ['response' => true, 'message' => 'Transaction saved', 'data' => $walletUpdate];
        } catch (\Exception $exception) {
            return ['response' => false, 'message' => $exception->getMessage()];
        }
    }

    public function update(int $id, array $data)
    {
        try {
            $updateWallet = Wallet::find($id);
            $updateWallet->status = strtolower($data['status']);
            $updateWallet->transaction_reference = $data['transaction_reference'];
            $updateWallet->remark = $data['remark'];
            $updateWallet->save();
            return ['response' => true, 'message' => 'Update successfully', 'data' => $updateWallet];
        } catch (\Exception $exception) {
            return ['response' => false, 'message' => $exception->getMessage()];
        }
    }

    public function walletCommission(Wallet $wallet, $transaction_type, $wallet_operation)
    {
        try {
            $commission = Commission::where('service_id', '=', $wallet->service_id)
                ->where('min_amount', '<=', $wallet->transacting_amount)
                ->where('max_amount', '>=', $wallet->transacting_amount)
                ->where('wef', '<=', date("Y-m-d"))
                ->limit(1)
                ->get();
            //logger($commission);
            if (count($commission) == 1) {
                $transacting_amount = $commission[0]->commission;
                if ($commission[0]->isPercentage) {
                    $transacting_amount = bcdiv($wallet->transacting_amount * $commission[0]->commission / 100, 1, 2);
                }
                $walletData = array(
                    'user_id' => $wallet->user_id,
                    'service_id' => $wallet->service_id,
                    'transaction_type' => $transaction_type,
                    'transaction_reference' => $wallet->transaction_reference,
                    'description' => $commission[0]->serviceName,
                    'transaction_date' => $wallet->transaction_date,
                    'status' => "success",
                    'wallet_operation' => $commission[0]->txnType,
                    'previous_balance' => $wallet->closing_balance,
                    'transacting_amount' => $transacting_amount,
                );
                $this->store($walletData);
            }
        } catch (\Exception $exception) {
            return response()->json(['response' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    /*Wallet settlement use cases
        1- Get the current user balance
        2- Choose Payout mode (IMPS/NEFT)
        3- Verify payout amount, account, and otp
        4- Insert data into tbl_wallet_settlement
        5- Update reference to user_wallet
        6- Deduct settlement charges from wallet
        7- if payout rejected or failed or canceled amount
            and settlement charge reverse to wallet
    */
    public function initWalletSettlement(Request $request)
    {
        try {
            //DB::beginTransaction();
            $input = json_decode($request->getContent(), true);

            $wallet = $this->checkBalance($request);
            $data = json_decode($wallet->getContent(), true);
            $settlement = new WalletSettlementController();
            if (Auth::user()->isBankAccountVerified) {
                if ($data['response']) {
                    if ($input['amount'] >= 5000 && $input['amount'] <= $data['data']['balance'] - 10) {
                        /*Deduct amount from wallet and insert record*/
                        $deductData = array(
                            'user_id' => Auth::user()->id,
                            'service_id' => 9,
                            'transaction_type' => "Settlement",
                            'description' => $input['amount'] . " settle to bank on " . date("d-m-Y H:i:s"),
                            'transaction_date' => date("Y-m-d H:i:s"),
                            'status' => "initiated",
                            'wallet_operation' => "dr",
                            'previous_balance' => $data['data']['balance'],
                            'transacting_amount' => $input['amount'],
                        );
                        $walletResponse = $this->store($deductData);
                        logger("Wallet payout record :", $walletResponse);
                        if ($walletResponse['response']) {
                            $deductWallet = $walletResponse['data'];
                            //deduct money from wallet
                            $settleData = array(
                                'user_id' => Auth::user()->id,
                                'service_id' => 9,
                                'txnType' => "BS",
                                'amount' => $input['amount'],
                                'bankname' => Auth::user()->bankname,
                                'ifsc' => Auth::user()->ifsc,
                                'accountNo' => strval(Auth::user()->accountNo),
                                'txnMedium' => $input['txnMedium'],
                                'walletReferenceNo' => $deductWallet->id
                            );
                            //logger("Settle Data",$settleData);
                            $settleResponse = $settlement->store($settleData);
                            logger("Settle response :", $settleResponse);
                            if ($settleResponse['response']) {
                                $settle = $settleResponse['data'];
                                $payout_data = array(
                                    'bene_account_number' => $settle->bene_account,
                                    'ifsc_code' => $settle->ifsc,
                                    'recepient_name' => Auth::user()->fname,
                                    'email_id' => Auth::user()->email,
                                    'mobile_number' => Auth::user()->contact,
                                    'debit_account_number' => config('keys.openBank.account'),
                                    'transaction_types_id' => $settle->txnMedium,
                                    'amount' => $input['amount'],
                                    'merchant_ref_id' => $settle->id,
                                    'purpose' => 'Wallet settlement'
                                );
                                //logger($payout_data);
                                $url = config('keys.openBank.url') . "payouts";
                                $token = "Bearer " . config('keys.openBank.apikey') . ":" . config('keys.openBank.secret');
                                $response = curl($url, "POST", json_encode($payout_data), $token);
                                logger("Payout response from bank:", $response);
                                if ($response['response']) {
                                    $data = $response['data'];
                                    /*Payout status find from response*/
                                    $payout_status = PayoutBankResponse::find($data->data->transaction_status_id);
                                    /*Find complete*/

                                    /*Wallet settlement operation started*/

                                    $settlementUpdateData = array(
                                        'txnId' => $data->data->open_transaction_ref_id,
                                        'response' => $data,
                                        'response_at' => date("Y-m-d H:i:s"),
                                        'remark' => $payout_status->status_message,
                                        'description' => $payout_status->interpreted_message,
                                        'status' => strtolower($payout_status->status_message),
                                        'bank_error_message' => null,
                                        'bank_transaction_ref_id' => null
                                    );
                                    $responseSettlement = $settlement->update(
                                        $data->data->merchant_ref_id,
                                        $settlementUpdateData);
                                    logger("Update Settlement", $responseSettlement);
                                    /*Settlement complete*/

                                    /*Update User Wallet*/
                                    if ($responseSettlement['response']) {
                                        $updateSettlement = $responseSettlement['data'];
                                        $updateWalletData = array(
                                            'status' => $payout_status->status_message,
                                            'transaction_reference' => $data->data->merchant_ref_id,
                                            'remark' => $payout_status->status_message
                                        );
                                        $updateWalletResponse = $this->update($updateSettlement->walletReferenceNo, $updateWalletData);
                                        logger("Wallet update response: ", $updateWalletResponse);
                                        if ($updateWalletResponse['response']) {
                                            $updateWallet = $updateWalletResponse['data'];
                                            $this->walletCommission($updateWallet, "Bank Settlement", "dr");
                                            //DB::commit();
                                            return response()->json(['response' => true, 'message' => 'Transaction initiated', 'data' => $payout_data]);
                                        } else {
                                            // DB::rollBack();
                                            return response()->json(['response' => false, 'message' => 'Unable to process wallet commission']);
                                        }
                                    } else {
                                        // DB::rollBack();
                                        return response()->json(['response' => false, 'message' => 'Unable to update settlement']);
                                    }
                                } else {
                                    /*Initiate Refund operation*/
                                    //DB::rollback();
                                    return response()->json(['response' => false,
                                        'message' => 'Transaction initiated fail. Try after some time',
                                        "errors" => $response['message']]);
                                }
                            } else {
                                //DB::rollBack();
                                return response()->json(['response' => false, 'message' => 'Unable to process settlement']);
                            }
                        } else {
                            // DB::rollBack();
                            return response()->json(['response' => false, 'message' => 'Unable to deduct wallet']);
                        }
                    } else {
                        return response()->json(['response' => false, 'message' => 'Invalid settlement amount.'], 422);
                    }
                } else {
                    return response()->json(['response' => false, 'message' => 'Wallet have not sufficient fund']);
                }
            } else {
                return response()->json(['response' => false, 'message' => 'Bank account not verified']);
            }
        } catch (\Exception $exception) {
            // DB::rollBack();
            logger($exception->getMessage());
            return response()->json(['response' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function getPayout(Request $request)
    {
        try {
            $input = json_decode($request->getContent(), true);
            $url = config('keys.openBank.url') . "payouts/" . $input['merchant_ref_id'];
            //logger($url);
            $token = "Bearer " . config('keys.openBank.apikey') . ":" . config('keys.openBank.secret');
            $response = curl($url, "GET", null, $token);
            if ($response['response']) {
                $data = $response['data']->data;
                $walletSettlement = WalletSettelment::find($input['merchant_ref_id']);
                $wallet = Wallet::find($walletSettlement->walletReferenceNo);
                $bankResponse = PayoutBankResponse::find($data->transaction_status_id);

                $walletSettlement->status = strtolower($bankResponse->status_message);
                $walletSettlement->remark = $bankResponse->status_message;
                $walletSettlement->description = $bankResponse->interpreted_message;
                $walletSettlement->update_response = $response['data'];
                $walletSettlement->update_response_at = date("Y-m-d H:i:s");
                $walletSettlement->bank_error_message = $data->bank_error_message;
                $walletSettlement->bank_transaction_ref_id = $data->bank_transaction_ref_id;
                $walletSettlement->save();

                $wallet->remark = $bankResponse->interpreted_message;
                $wallet->status = strtolower($bankResponse->status_message);
                $wallet->save();
                return response()->json(['response' => true, 'message' => 'Check complete', 'data' => $walletSettlement]);
            } else {
                return response()->json($response, $response['response_code']);
            }
            //return $response;
        } catch (\Exception $exception) {
            return response()->json(['response' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function userWiseBalance()
    {
        try {
            $query = "select u.id, u.fname, u.lname,
       (select uw.closing_balance from user_wallet uw where uw.user_id = u.id
       order by uw.id desc limit 1) as balance from users u where u.role=4
having balance is not null";

             $data = DB::select($query);

             return response()->json(['response'=>true,'message'=>'Balance fetched','data'=>$data]);
        } catch (\Exception $exception) {
            return response()->json(['response' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function walletBalance(){
        try {
            $query="select sum((select uw.closing_balance
       from user_wallet uw
       where uw.user_id = u.id
       order by uw.id desc
       limit 1)) as balance
from users u where u.role=4 having balance is not null";
            $data = DB::select($query);
            return response()->json(['response'=>true,'message'=>'Balance Fetched','data'=>$data]);
        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }

    public function todayBusiness(){
        try {
            $query="select sum(transacting_amount) as amount from user_wallet
where date(created_at)='". date('Y-m-d') ."' and status='success' and wallet_operation='cr'";
            $data=DB::select($query);
                return response()->json(['response'=>true,'message'=>'Balance Fetched','data'=>$data]);
        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }

    public function beneVerification(Request $request)
    {
        try {
            //redirect('login');
            $settlement = new WalletSettlementController();
            if (isset(Auth::user()->id)) {
                $wallet = $this->checkBalance($request);
                $walletContent = json_decode($wallet->getContent(), true);
                if ($walletContent['response'] && $walletContent['data']['balance'] > 4) {
                    $deductData = array(
                        'user_id' => Auth::user()->id,
                        'service_id' => 10,
                        'transaction_type' => "Bene Verification",
                        'description' => 1 . " settle to bank on " . date("d-m-Y H:i:s"),
                        'transaction_date' => date("Y-m-d H:i:s"),
                        'status' => "initiated",
                        'wallet_operation' => "dr",
                        'previous_balance' => $walletContent['data']['balance'],
                        'transacting_amount' => 1,
                    );
                    $walletResponse = $this->store($deductData);
                    logger("Wallet deducted record :", $walletResponse);
                    if ($walletResponse['response']) {
                        $deductWallet = $walletResponse['data'];
                        //wallet settlement data
                        $settleData = array(
                            'user_id' => Auth::user()->id,
                            'service_id' => 10,
                            'txnType' => "BV",
                            'amount' => 1,
                            'bankname' => Auth::user()->bankname,
                            'ifsc' => Auth::user()->ifsc,
                            'accountNo' => strval(Auth::user()->accountNo),
                            'txnMedium' => "4",
                            'walletReferenceNo' => $deductWallet->id
                        );
                        $settleResponse = $settlement->store($settleData);
                        logger("Settle response :", $settleResponse);
                        if ($settleResponse['response']) {
                            $settle = $settleResponse['data'];
                            $accountVerificationData = array(
                                'bene_account_number' => Auth::user()->accountNo,
                                'ifsc_code' => Auth::user()->ifsc,
                                'recepient_name' => Auth::user()->fname . ' ' . Auth::user()->lname,
                                'email_id' => Auth::user()->email,
                                'mobile_number' => Auth::user()->contact,
                                'merchant_ref_id' => $settle->id
                            );
                            $url = $url = config('keys.openBank.url') . "bank_account/verify";
                            $token = "Bearer " . config('keys.openBank.apikey') . ":" . config('keys.openBank.secret');
                            $response = curl($url, "POST", json_encode($accountVerificationData), $token);
                            logger($response);
                            if ($response['response']) {
                                $verificationData = $response['data']['data'][0];
                                $settlementUpdateData = array(
                                    'txnId' => $verificationData->open_transaction_ref_id,
                                    'response' => $response['data'],
                                    'response_at' => date("Y-m-d H:i:s"),
                                    'remark' => $verificationData->verification_status,
                                    'description' => $verificationData->verification_message,
                                    'status' => strtolower($verificationData->verification_status),
                                    'bank_error_message' => null,
                                    'bank_transaction_ref_id' => $verificationData->bank_ref_num
                                );
                                $responseSettlement = $settlement->update(
                                    $verificationData->merchant_ref_id,
                                    $settlementUpdateData);
                                if ($responseSettlement['response']) {
                                    $updateSettlement = $responseSettlement['data'];
                                    $updateWalletData = array(
                                        'status' => $verificationData->verification_status,
                                        'transaction_reference' => $verificationData->merchant_ref_id,
                                        'remark' => $verificationData->verification_message
                                    );
                                    $updateWalletResponse = $this->update($updateSettlement->walletReferenceNo, $updateWalletData);
                                    logger("Wallet update response: ", $updateWalletResponse);
                                    if ($updateWalletResponse['response']) {
                                        $updateWallet = $updateWalletResponse['data'];
                                        $this->walletCommission($updateWallet, "Bene Verification", "dr");
                                        /* User bank account verification status update*/
                                        $user = User::find(Auth::user()->id);
                                        $user->bav_at = date("Y-m-d H:i:s");
                                        $user->bav_remark = $verificationData->verification_message;
                                        $user->isBankAccountVerified = (strtolower($verificationData->verification_status) == 'success') ? true : false;
                                        $user->save();
                                        return response()->json(['response' => true, 'message' => 'Transaction initiated', 'data' => $user]);
                                    } else {
                                        // DB::rollBack();
                                        return response()->json($updateWalletResponse);
                                    }
                                } else {
                                    return response()->json($responseSettlement);
                                }

                            } else {
                                return response()->json($response);
                            }
                        } else {
                            return response()->json($settleResponse);
                        }
                    } else {
                        return response()->json($walletResponse);
                    }

                } else {
                    return response()->json(['response' => false, 'message' => 'Low wallet balance']);
                }
            } else {
                redirect('login');
            }

        } catch (\Exception $exception) {
            return response()->json(['response' => false, 'message' => $exception->getMessage()], 500);
        }
    }

    public function walletRefund(){
        try {

        }catch (\Exception $exception){
            return response()->json(['response' => false, 'message' => $exception->getMessage()], 500);
        }
    }

}
