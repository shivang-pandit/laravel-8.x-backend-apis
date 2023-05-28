<?php

namespace App\Http\Controllers\API;

use App\Helpers\HelperFunctions;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerLoanApplyRequest;
use App\Http\Requests\CustomerPaymentInstallmentRequest;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\LoanPayments;
use Illuminate\Support\Facades\Auth;
use RestResponseFactory;

class LoanController extends Controller
{
    /**
     * @param CustomerLoanApplyRequest $request
     * @return string JSON
     */
    public function apply(CustomerLoanApplyRequest $request)
    {
        $customer = Auth::user();
        if (!$customer) {
            return RestResponseFactory::not_found([], 'Customer not found.')->toJSON();
        }

        $activeLoan = Loan::where('customer_id', $customer['id'])->whereIn('status', ['pending', 'approved'])->first();
        if ($activeLoan) {
            return RestResponseFactory::forbidden([], 'You can not apply another loan if your loan is currently active.')->toJSON();
        }

        $loan = Loan::create([
            'customer_id' => $customer['id'],
            'amount' => $request['amount'],
            'term' => $request['term'],
        ]);


        return RestResponseFactory::success($loan, 'Loan applied successfully!')->toJSON();
    }

    /**
     * @param Loan $loan
     * @return string JSON
     */
    public function approveLoan(Loan $loan)
    {
        $admin = Auth::user();
        if (!$admin) {
            return RestResponseFactory::not_found([], 'Admin not found.')->toJSON();
        }

        if ($loan->status === "approved") {
            return RestResponseFactory::error([], 'Loan already processed.')->toJSON();
        }

        if ($loan->status === "disapproved") {
            return RestResponseFactory::error([], 'Loan already disapproved.')->toJSON();
        }

        if ($loan->status === "paid") {
            return RestResponseFactory::error([], 'Loan already closed.')->toJSON();
        }

        $loan->update([
            'status' => 'approved',
            'approved_by_id' => $admin['id']
        ]);


        $installments = HelperFunctions::calculateLoanInstallment($loan->amount, $loan->term, 'week');
        $loanPayments = [];
        for ($i = 0; $i < count($installments); $i++) {
            $loanPayments[] = new LoanPayments([
                'due_amount' => $installments[$i]['amount'],
                'payment_due_date' => $installments[$i]['payment_due_date'],

            ]);
        }
        if (count($loanPayments) > 0) {
            $loan->loanPayments()->saveMany($loanPayments);
        }

        return RestResponseFactory::success($loan->load('loanPayments'), 'Loan approved successfully!')->toJSON();
    }

    /**
     * @return string JSON
     */
    public function customerLoans()
    {
        $customer = Auth::user();
        if (!$customer) {
            return RestResponseFactory::not_found([], 'Customer not found.')->toJSON();
        }

        $loan = Loan::with('loanPayments')->where('customer_id', $customer['id'])->get();
        if (!$loan) {
            return RestResponseFactory::not_found([], 'You haven\'t applied any loan yet.')->toJSON();
        }

        return RestResponseFactory::success($loan, 'Loans')->toJSON();
    }

    /**
     * @return string JSON
     */
    public function customersLoan()
    {
        $admin = Auth::user();
        if (!$admin) {
            return RestResponseFactory::not_found([], 'Admin not found.')->toJSON();
        }


        $loan = Customer::with('loans.loanPayments')->get();
        if (!$loan) {
            return RestResponseFactory::not_found([], 'You haven\'t applied any loan yet.')->toJSON();
        }

        return RestResponseFactory::success($loan, 'Loans')->toJSON();
    }

    /**
     * @param LoanPayments $loanPayment
     * @param CustomerPaymentInstallmentRequest $request
     * @return string JSON
     */
    public function payInstallment(LoanPayments $loanPayment, CustomerPaymentInstallmentRequest $request)
    {

        $customer = Auth::user();
        if (!$customer) {
            return RestResponseFactory::not_found([], 'Customer not found.')->toJSON();
        }

        $loan = Loan::find($loanPayment->loan_id);

        if ($loan['customer_id'] !== $customer['id']) {
            return RestResponseFactory::badrequest([], 'Invalid loan detail.')->toJSON();
        }

        if ($loan['status'] === "paid") {
            return RestResponseFactory::badrequest([], 'Loan has been fully paid.')->toJSON();
        }

        if ($loanPayment['status'] === "paid") {
            return RestResponseFactory::badrequest([], 'Installment already paid.')->toJSON();
        }

        if ($request['amount'] > $loan['total_remain']) {
            return RestResponseFactory::badrequest([], 'You can not pay more than ' . $loan['total_remain'])->toJSON();
        }

        if (
            ($request['amount'] < $loan['total_remain']) &&
            $loanPayment['due_amount'] > $request['amount']
        ) {
            return RestResponseFactory::badrequest([], 'Your payment amount should not less then due amount(' . $loanPayment['due_amount'] . ')')->toJSON();
        }

        $loanPayment->update([
            'paid_amount' => (float)$request['amount'],
            'status' => 'paid'
        ]);

        return RestResponseFactory::success($loan->refresh(), 'Installment paid successfully.')->toJSON();
    }
}
