<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Order;
use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Admin\HandleRefundRequest;
use App\Http\Requests\Request;
use App\Models\Order as AppOrder;
use App\Models\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Carbon\Carbon;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Dcat;

class OrderController extends AdminController
{
    use ValidatesRequests;
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Order('user','items'), function (Grid $grid) {
            $grid->model()->orderBy('updated_at', 'desc');

            $grid->column('id')->sortable();
            $grid->column('no')->filter(
                Grid\Column\Filter\Like::make()
            );
            $grid->column('user.name','买家')->filter(
                Grid\Column\Filter\Like::make()
            );
            $grid->column('total_amount')->filter(
                Grid\Column\Filter\Between::make()
            );
            $grid->column('payment_method')->display( function ($value) {
                return $value === 'alipay' ? '支付宝支付' : ($value === 'alipay' ? '微信支付' : '未支付');
            })->label('success')->filter(
                Grid\Column\Filter\In::make([
                    'wechat' => '微信支付',
                    'alipay'  => '支付宝支付',
                ])
            );
            $grid->column('closed')->display(function ($value) {
                return $value ? '是' : '否';
            })->label([
                1 => 'danger',
                0 => 'default',
            ])->filter(
                Grid\Column\Filter\In::make([
                    0 => '否',
                    1 => '是',
                ])
            );
            $grid->column('reviewed')->display( function ($value) {
                return $value ? '是' : '否';
            })->label([
                1 => 'default',
                0 => 'primary',
            ])->filter(
                Grid\Column\Filter\In::make([
                    0 => '否',
                    1 => '是',
                ])
            );
            $grid->column('ship_status')->filter(
                Grid\Column\Filter\In::make([
                    'pending' => '未发货',
                    'delivered' => '已发货',
                    'received' => '已收货',
                ])
            )->display( function ($value) {
                return Order::$shipStatusMap[$value];
            });
            $grid->column('refund_status')->display( function ($value) {
                return Order::$refundStatusMap[$value];
            })->label([
                'pending' => 'default',
                'applied' => 'yellow',
                'failed'  => 'danger',
                'success' => 'success',
            ])->filter(
                Grid\Column\Filter\In::make([
                    'pending' => '未退款',
                    'applied' => '已申请退款',
                    'failed'  => '退款失败',
                    'success' => '退款成功',
                ])
            );
            $grid->column('paid_at')->display( function ($value) {
                return $value ? $value : '未支付';
            })->filter(
                Grid\Column\Filter\Between::make()->datetime()
            );
            $grid->column('created_at')->filter(
                Grid\Column\Filter\Between::make()->datetime()
            )->sortable();

            // 操作
            $grid->disableCreateButton();
            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
            });

            //导出
            $data = [
                'no' => '订单号',
                'user_name' => '买家',
                'total_amount' => '总金额',
                'payment_method' => '支付方式',
                'created_at' => '创建时间',
            ];

            $grid->export()->titles($data)->rows(function (array $rows) {
                foreach ($rows as $index => &$row) {
                    $row['user_name'] = User::where('id',$row['user_id'])->value('name');
                }
                return $rows;
            })->filename('Witcier Mall订单'.Carbon::now());
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id')->width(3);
                $filter->like('user.name','买家')->width(3);
                $filter->between('created_at', '创建时间')->datetime()->width(3);
            });
            // $grid->quickSearch('user.name','')->placeholder('快速搜索...');

            $grid->selector(function (Grid\Tools\Selector $selector) {
                $selector->selectOne('payment_method', '支付方式', ['alipay' => '支付宝支付', 'wechat' => '微信支付']);
                $selector->selectOne('closed', '订单关闭', [1 => '是', 0 => '否']);
                $selector->selectOne('ship_status', '物流状态', ['pending' => '未发货', 'delivered' => '已发货', 'received' => '已收货']);
                $selector->selectOne('total_amount', '订单金额', ['0-599', '600-1999', '1999-4999', '5000+'], function ($query, $value) {
                    $between = [
                        [0, 599],
                        [600, 1999],
                        [2000, 4999],
                        [5000,10000000],
                    ];
                
                    $value = current($value);
                    $query->whereBetween('total_amount', $between[$value]);
                });
            });

            $grid->toolsWithOutline(false);
            
            $grid->filter(function (Grid\Filter $filter) {
                // 更改为 panel 布局
                $filter->panel();
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    public function show($id, Content $content)
    {

        $order = AppOrder::find($id);
        $data = [
            'order' => $order
        ];
        return $content
            ->title('订单')
            ->description('详情')
            ->body($this->_detail($data));
    }

    private function _detail($data)
    {
        return view('admin/orders/show', $data);
    }

    public function ship(AppOrder $order, Request $request)
    {
        // 判断当前订单是否已经支付
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未付款');
        }
        // 众筹订单只有众筹成功才能发货
        if ($order->type === AppOrder::TYPE_CROWDFUNDING && $order->items[0]->product->crowdfunding->status !== CrowdfundingProduct::STATUS_SUCCESS) {
            throw new InvalidRequestException('众筹订单只能在众筹成功之后发货');
        }
        // 判断订单是否已经发货
        if ($order->ship_status !== Order::SHIP_STATUS_PENDING) {
            throw new InvalidRequestException('该订单已发货');
        }

        $data = $this->validate($request, [
            'express_company' => ['required'],
            'express_no'      => ['required'],
        ],[],[
            'express_company' => '物流公司',
            'express_no'      => '物流单号',
        ]);

        // 将订单发货状态改为发货
        $order->update([
            'ship_status' => Order::SHIP_STATUS_DELIVERED,
            'ship_data'   => $data,
        ]);

        return redirect()->back();
    }

    public function handleRefund(AppOrder $order, HandleRefundRequest $request)
    {
        // 判断订单状态是否正确
        if ($order->refund_status !== Order::REFUND_STATUS_APPLIED) {
            // return $this->error('订单状态不正确');
            throw new InvalidRequestException('订单状态不正确');
        }
        // 是否同意退款
        if ($request->input('agree')) {
           // 清空拒绝退款理由
        //    $extra = $order->extre ?: [];
        //    unset($extra['refund_disagree_reason']);
        //    $order->update([
        //        'extra' => $extra,
        //    ]);
           $this->_refundOrder($order);
        } else {
            // 将拒绝退款的理由放到订单的 extra 字段
            $extra = $order->extre ?: [];
            $extra['refund_disagree_reason'] = $request->input('reason'); 

            // 将订单的退款状态改为未退款
            $order->update([
                'refund_status' => Order::REFUND_STATUS_PENDING,
                'extra'         => $extra,
            ]);
        }

        return $order;
    }

    protected function _refundOrder(AppOrder $order)
    {
        // 判断该订单的支付方式
        switch ($order->payment_method) {
            case 'wechat':
                // 生成退款订单号
                $refundNo = AppOrder::getAvailableRefundNo();
                app('wechat_pay')->refund([
                    'out_trade_no' => $order->no,
                    'total_fee' => $order->total_amount * 100,
                    'refund_fee' => $order->total_amount * 100,
                    'out_refund_no' => $refundNo,
                    // 微信支付的退款结果并不是实时返回的，而是通过退款回调来通知，因此这里需要配上退款回调接口地址
                    'notify_url' => route('payment.wechat.refund_notify'),
                ]);
                // 将订单的退款状态该为退款中
                $order->update([
                    'refund_no' => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'alipay':
                // 生成退款订单号
                $refundNo = AppOrder::getAvailableRefundNo();
                // 调用支付宝的支付实例的 refund 方法
                $ret = app('alipay')->refund([
                    'out_trade_no' => $order->no,
                    'refund_amount' => $order->total_amount,
                    'out_request_no' => $refundNo,
                ]);
                 // 根据支付宝的文档，如果返回值里有 sub_code 字段说明退款失败
                 if ($ret->sub_code) {
                     // 将退款失败的保存存入 extra 字段
                     $extra = $order->extra;
                     $extra['refund_failed_code'] = $ret->sub_code;
                     // 将订单的退款状态标记为退款失败
                     $order->update([
                         'refund_no' => $refundNo,
                         'refund_status' => AppOrder::REFUND_STATUS_FAILED,
                         'extra' => $extra,
                     ]);
                 } else {
                     // 将订单的退款状态标记为退款成功
                     $order->update([
                         'refund_no' => $refundNo,
                         'refund_status' => AppOrder::REFUND_STATUS_SUCCESS,
                     ]);
                 }
                break;
            default:
                throw new InternalException('未知支付方式：'.$order->payment_method);
                break;
        }
    }
}
