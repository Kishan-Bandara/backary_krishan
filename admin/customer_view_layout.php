<!DOCTYPE html>
<?php
function getShippingAddressAvailability($shippingAddressId) {
    try {
        $db = new Database();
        $row = $db->getRow('SELECT id, mon, tue, wed, thu, fri, sat, sun FROM shipping_address_availability WHERE shipping_address_id = ? LIMIT 1', [$shippingAddressId]);
        return $row ?: null;
    } catch (Exception $e) {
        return null;
    }
}
?>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
    <meta charset="utf-8" />
    <title>Customer</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <?php include('common/head.php'); ?>
    <style>
        .section-card { background: #ffffff; border: 1px solid #dde3ec; border-radius: 8px; padding: 24px; box-shadow: 0 6px 18px rgba(52, 73, 94, 0.08); margin-bottom: 24px; }
        .section-card h4 { margin-top: 0; margin-bottom: 18px; font-size: 15px; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; color: #5d6d8a; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; color: #4a5a73; }
        .info-row span:first-child { font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; color: #6e7a90; }
        .shipping-address-item { border: 1px solid #e7edf5; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #f9fbff; position: relative; }
        .shipping-address-item .remove-shipping-address { position: absolute; top: 12px; right: 12px; }
        .shipping-address-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .shipping-address-controls .form-group { margin-bottom: 0; }
        .form-actions { display: flex; justify-content: flex-end; gap: 12px; }
        .shipping-view-list { list-style: none; padding: 0; margin: 0; }
        .shipping-view-list li { border: 1px solid #e7edf5; border-radius: 8px; padding: 14px 16px; margin-bottom: 10px; background: #f7faff; }
        .shipping-view-list strong { display: block; font-size: 12px; color: #4a5a73; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.05em; }
        .customer-logo-preview img { max-width: 100%; height: auto; border-radius: 6px; margin-bottom: 14px; border: 1px solid #dbe2ef; }
        .default-indicator { font-weight: 600; text-transform: uppercase; font-size: 12px; color: #5d6d8a; letter-spacing: 0.05em; }
        @media (max-width: 767px) {
            .form-actions { flex-direction: column; align-items: stretch; }
            .shipping-address-controls { flex-direction: column; align-items: flex-start; gap: 8px; }
        }
    </style>
</head>
<body class="page-sidebar-closed-hide-logo page-content-white">
    <?php include('common/manubar.php'); ?>
    <div class="clearfix"></div>
    <div class="page-container">
        <div class="page-sidebar-wrapper">
            <?php include('common/sidebar.php'); ?>
        </div>
        <div class="page-content-wrapper">
            <div class="page-content">
                <div class="page-bar">
                    <ul class="page-breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                            <i class="fa fa-circle"></i>
                        </li>
                        <li>
                            <span>Customer</span>
                        </li>
                    </ul>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="alert <?php echo h($MessageClass ?: 'alert-info'); ?> alert-dismissable">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                        <?php echo nl2br(h($message)); ?>
                    </div>
                <?php endif; ?>

                <div class="portlet-body">
                    <div class="tabbable-custom">
                        <ul class="nav nav-tabs">
                            <li class="active">
                                <a href="#tab_profile" data-toggle="tab" aria-expanded="true">Customer Profile</a>
                            </li>
                            <li>
                                <a href="#tab_sales" data-toggle="tab" aria-expanded="false">Customer Sales</a>
                            </li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane active" id="tab_profile">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="section-card">
                                            <h4>Customer Overview</h4>
                                            <?php if ($formData['customer_logo']): ?>
                                                <div class="customer-logo-preview">
                                                    <img src="<?php echo h($formData['customer_logo']); ?>" alt="Customer Logo">
                                                </div>
                                            <?php endif; ?>
                                            <div class="info-row"><span>Code</span><span><?php echo h($formData['customer_code']); ?></span></div>
                                            <div class="info-row"><span>Status</span><span><?php echo h($isActiveLabel); ?></span></div>
                                            <div class="info-row"><span>Account</span><span><?php echo h($accountHoldLabel); ?></span></div>
                                            <div class="info-row"><span>Lock</span><span><?php echo h($lockedLabel); ?></span></div>
                                            <div class="info-row"><span>Credit Limit</span><span><?php echo h($creditLimitDisplay); ?></span></div>
                                            <div class="info-row"><span>Outstanding</span><span><?php echo h($outstandingDisplay); ?></span></div>
                                            <div class="info-row"><span>VAT Status</span><span><?php echo h($vatRegisteredLabel); ?></span></div>
                                            <?php if ($formData['abn_no'] !== ''): ?>
                                                <div class="info-row"><span>ABN</span><span><?php echo h($formData['abn_no']); ?></span></div>
                                            <?php endif; ?>
                                            <?php if ($formData['acn_no'] !== ''): ?>
                                                <div class="info-row"><span>ACN</span><span><?php echo h($formData['acn_no']); ?></span></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($shippingFormData)): ?>
                                            <div class="section-card">
                                                <h4>Shipping Locations</h4>
                                                <ul class="shipping-view-list">
                                                    <?php foreach ($shippingFormData as $address): ?>
                                                        <li>
                                                            <strong>
                                                                <?php echo h($address['label'] !== '' ? $address['label'] : 'Shipping Address'); ?>
                                                                <?php if ((int) ($address['is_default'] ?? 0) === 1): ?>
                                                                    <span class="label label-success" style="margin-left: 4px;">Default</span>
                                                                <?php endif; ?>
                                                            </strong>
                                                            <div><?php echo h($address['address_line_1']); ?></div>
                                                            <?php if ($address['address_line_2'] !== ''): ?>
                                                                <div><?php echo h($address['address_line_2']); ?></div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <?php echo h($address['city']); ?>
                                                                <?php if ($address['postal_code'] !== ''): ?>
                                                                    <span>, <?php echo h($address['postal_code']); ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php if ($address['contact_no'] !== ''): ?>
                                                                <div><?php echo h($address['contact_no']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if ($address['attribute_1'] !== '' || $address['attribute_2'] !== '' || $address['attribute_3'] !== ''): ?>
                                                                <div class="text-muted" style="font-size: 12px; margin-top: 6px;">
                                                                    <?php if ($address['attribute_1'] !== ''): ?>
                                                                        <span><?php echo h($address['attribute_1']); ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if ($address['attribute_2'] !== ''): ?>
                                                                        <span><?php echo h($address['attribute_2']); ?></span>
                                                                    <?php endif; ?>
                                                                    <?php if ($address['attribute_3'] !== ''): ?>
                                                                        <span><?php echo h($address['attribute_3']); ?></span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php
                                                            $availability = getShippingAddressAvailability($address['id']);
                                                            if ($availability): ?>
                                                                <div class="text-muted" style="font-size: 12px; margin-top: 6px;">
                                                                    <strong>Delivery Days:</strong>
                                                                    <?php
                                                                    $days = [];
                                                                    $dayLabels = ['mon' => 'Mon', 'tue' => 'Tue', 'wed' => 'Wed', 'thu' => 'Thu', 'fri' => 'Fri', 'sat' => 'Sat', 'sun' => 'Sun'];
                                                                    foreach ($dayLabels as $key => $label) {
                                                                        if ($availability[$key]) {
                                                                            $days[] = $label;
                                                                        }
                                                                    }
                                                                    if (empty($days)) {
                                                                        echo '<span class="text-muted">No delivery days set</span>';
                                                                    } else {
                                                                        echo '<span class="text-success">' . h(implode(', ', $days)) . '</span>';
                                                                    }
                                                                    ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="text-muted" style="font-size: 12px; margin-top: 6px;">
                                                                    <strong>Delivery Days:</strong> <span class="text-muted">All days available</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($formData['customer_note'] !== ''): ?>
                                            <div class="section-card">
                                                <h4>Customer Note</h4>
                                                <p><?php echo nl2br(h($formData['customer_note'])); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-8">
                                        <form class="form-horizontal" method="post" enctype="multipart/form-data">
                                            <input type="hidden" name="original_shipping_ids" value="<?php echo h(implode(',', $originalShippingIds)); ?>">

                                            <div class="section-card">
                                                <h4>Identity &amp; Contact</h4>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Customer Code<span class="required">*</span></label>
                                                            <input type="text" class="form-control" name="customer_code" value="<?php echo h($formData['customer_code']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Customer Name<span class="required">*</span></label>
                                                            <input type="text" class="form-control" name="customer_name" value="<?php echo h($formData['customer_name']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Email</label>
                                                            <input type="email" class="form-control" name="customer_email" value="<?php echo h($formData['customer_email']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Phone</label>
                                                            <input type="text" class="form-control" name="customer_phone" value="<?php echo h($formData['customer_phone']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Mobile</label>
                                                            <input type="text" class="form-control" name="customer_mobile" value="<?php echo h($formData['customer_mobile']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="section-card">
                                                <h4>Billing Address</h4>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Address Line 1<span class="required">*</span></label>
                                                            <input type="text" class="form-control" name="address_line_1" value="<?php echo h($formData['address_line_1']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Address Line 2</label>
                                                            <input type="text" class="form-control" name="address_line_2" value="<?php echo h($formData['address_line_2']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>City / Town</label>
                                                            <input type="text" class="form-control" name="city" value="<?php echo h($formData['city']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Postal Code</label>
                                                            <input type="text" class="form-control" name="postal_code" value="<?php echo h($formData['postal_code']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="section-card">
                                                <h4>Financial &amp; Tax</h4>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Credit Limit</label>
                                                            <input type="text" class="form-control" name="credit_limit" value="<?php echo h($formData['credit_limit']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Payment Terms ID</label>
                                                            <input type="text" class="form-control" name="payment_terms_id" value="<?php echo h($formData['payment_terms_id']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Price Type ID</label>
                                                            <input type="text" class="form-control" name="customer_price_type_id" value="<?php echo h($formData['customer_price_type_id']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>GST / VAT Number</label>
                                                            <input type="text" class="form-control" name="gst_no" value="<?php echo h($formData['gst_no']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>ABN</label>
                                                            <input type="text" class="form-control" name="abn_no" value="<?php echo h($formData['abn_no']); ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>ACN</label>
                                                            <input type="text" class="form-control" name="acn_no" value="<?php echo h($formData['acn_no']); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="checkbox-list">
                                                            <label class="checkbox-inline">
                                                                <input type="checkbox" name="vat_registered" value="1" <?php echo $formData['vat_registered'] ? 'checked' : ''; ?>> VAT Registered
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="checkbox-list">
                                                            <label class="checkbox-inline">
                                                                <input type="checkbox" name="account_hold" value="1" <?php echo $formData['account_hold'] ? 'checked' : ''; ?>> Account Hold
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="checkbox-list">
                                                            <label class="checkbox-inline">
                                                                <input type="checkbox" name="is_active" value="1" <?php echo $formData['is_active'] ? 'checked' : ''; ?>> Active
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="checkbox-list">
                                                            <label class="checkbox-inline">
                                                                <input type="checkbox" name="locked" value="1" <?php echo $formData['locked'] ? 'checked' : ''; ?>> Locked
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="section-card">
                                                <h4>Notes &amp; Branding</h4>
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <div class="form-group">
                                                            <label>Customer Note</label>
                                                            <textarea class="form-control" rows="4" name="customer_note"><?php echo h($formData['customer_note']); ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Customer Logo</label>
                                                            <input type="file" class="form-control" name="customer_logo" accept="image/jpeg,image/png">
                                                            <p class="help-block">JPG or PNG only. Leave blank to keep existing.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="section-card">
                                                <h4>Shipping Addresses</h4>
                                                <p class="text-muted" style="margin-top:-10px;">Manage delivery destinations for this customer. Mark one address as the default.</p>
                                                <div id="shippingAddresses">
                                                    <?php foreach ($shippingFormData as $index => $address): ?>
                                                        <div class="shipping-address-item" data-index="<?php echo $index; ?>">
                                                            <input type="hidden" name="shipping_id[<?php echo $index; ?>]" value="<?php echo (int) ($address['id'] ?? 0); ?>">
                                                            <button type="button" class="btn btn-xs red remove-shipping-address" <?php echo $index === 0 ? 'style="display:none;"' : ''; ?>><i class="fa fa-trash"></i></button>
                                                            <div class="shipping-address-controls">
                                                                <div class="form-inline">
                                                                    <label class="default-indicator">
                                                                        <input type="radio" name="shipping_default" value="<?php echo $index; ?>" <?php echo !empty($address['is_default']) ? 'checked' : ''; ?>> Default
                                                                    </label>
                                                                </div>
                                                                <div class="form-group" style="margin-bottom:0;">
                                                                    <input type="text" class="form-control" name="shipping_label[<?php echo $index; ?>]" value="<?php echo h($address['label']); ?>" placeholder="Label (e.g. Warehouse)">
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Address Line 1<span class="required">*</span></label>
                                                                        <input type="text" class="form-control" name="shipping_address_line_1[<?php echo $index; ?>]" value="<?php echo h($address['address_line_1']); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Address Line 2</label>
                                                                        <input type="text" class="form-control" name="shipping_address_line_2[<?php echo $index; ?>]" value="<?php echo h($address['address_line_2']); ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label>City / Town</label>
                                                                        <input type="text" class="form-control" name="shipping_city[<?php echo $index; ?>]" value="<?php echo h($address['city']); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label>Postal Code</label>
                                                                        <input type="text" class="form-control" name="shipping_postal_code[<?php echo $index; ?>]" value="<?php echo h($address['postal_code']); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label>Contact Number</label>
                                                                        <input type="text" class="form-control" name="shipping_contact_no[<?php echo $index; ?>]" value="<?php echo h($address['contact_no']); ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label>Attribute 1</label>
                                                                        <input type="text" class="form-control" name="shipping_attribute_1[<?php echo $index; ?>]" value="<?php echo h($address['attribute_1']); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label>Attribute 2</label>
                                                                        <input type="text" class="form-control" name="shipping_attribute_2[<?php echo $index; ?>]" value="<?php echo h($address['attribute_2']); ?>">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="form-group">
                                                                        <label>Attribute 3</label>
                                                                        <input type="text" class="form-control" name="shipping_attribute_3[<?php echo $index; ?>]" value="<?php echo h($address['attribute_3']); ?>">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button type="button" class="btn btn-default" id="addShippingAddress"><i class="fa fa-plus"></i> Add Shipping Address</button>
                                            </div>

                                            <div class="section-card">
                                                <div class="form-actions">
                                                    <button type="submit" class="btn green" name="sub"><i class="fa fa-check"></i> Update Customer</button>
                                                    <a href="manage-customer.php" class="btn default">Cancel</a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="tab_sales">
                                <div class="section-card">
                                    <h4>Invoice History</h4>
                                    <?php if (empty($invoiceHistory)): ?>
                                        <p class="text-muted">No invoices found for this customer.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover" id="invoiceHistoryTable">
                                                <thead>
                                                    <tr>
                                                        <th>Invoice Code</th>
                                                        <th>Date</th>
                                                        <th class="text-right">Net Amount</th>
                                                        <th class="text-right">Gross Amount</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($invoiceHistory as $invoice): ?>
                                                        <tr>
                                                            <td><?php echo h($invoice['invoice_h_code'] ?? ''); ?></td>
                                                            <td><?php echo h($invoice['invoice_h_date'] ?? ''); ?></td>
                                                            <td class="text-right"><?php echo number_format((float) ($invoice['invoice_h_net_value'] ?? 0), 2); ?></td>
                                                            <td class="text-right"><?php echo number_format((float) ($invoice['invoice_h_gross_value'] ?? 0), 2); ?></td>
                                                            <td>
                                                                <?php
                                                                    $statusValue = (int) ($invoice['invoice_h_status'] ?? 0);
                                                                    echo $statusValue === 1 ? 'Completed' : ($statusValue === 0 ? 'Pending' : 'Other');
                                                                ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('common/footer.php'); ?>
    <!--[if lt IE 9]>
<script src="assets/global/plugins/respond.min.js"></script>
<script src="assets/global/plugins/excanvas.min.js"></script>
<![endif]-->
    <script src="assets/global/plugins/jquery.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/js.cookie.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/jquery-slimscroll/jquery.slimscroll.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/jquery.blockui.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/uniform/jquery.uniform.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/bootstrap-switch/js/bootstrap-switch.min.js" type="text/javascript"></script>
    <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
    <script src="assets/global/scripts/app.min.js" type="text/javascript"></script>
    <script src="assets/pages/scripts/table-datatables-responsive.min.js" type="text/javascript"></script>
    <script src="assets/layouts/layout/scripts/layout.min.js" type="text/javascript"></script>
    <script src="assets/layouts/layout/scripts/demo.min.js" type="text/javascript"></script>
    <script src="assets/layouts/global/scripts/quick-sidebar.min.js" type="text/javascript"></script>
    <script type="text/template" id="shippingAddressTemplate">
        <div class="shipping-address-item" data-index="__INDEX__">
            <input type="hidden" name="shipping_id[__INDEX__]" value="0">
            <button type="button" class="btn btn-xs red remove-shipping-address"><i class="fa fa-trash"></i></button>
            <div class="shipping-address-controls">
                <div class="form-inline">
                    <label class="default-indicator">
                        <input type="radio" name="shipping_default" value="__INDEX__"> Default
                    </label>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <input type="text" class="form-control" name="shipping_label[__INDEX__]" placeholder="Label (e.g. Warehouse)">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Address Line 1<span class="required">*</span></label>
                        <input type="text" class="form-control" name="shipping_address_line_1[__INDEX__]">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Address Line 2</label>
                        <input type="text" class="form-control" name="shipping_address_line_2[__INDEX__]">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>City / Town</label>
                        <input type="text" class="form-control" name="shipping_city[__INDEX__]">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" class="form-control" name="shipping_postal_code[__INDEX__]">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" class="form-control" name="shipping_contact_no[__INDEX__]">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Attribute 1</label>
                        <input type="text" class="form-control" name="shipping_attribute_1[__INDEX__]">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Attribute 2</label>
                        <input type="text" class="form-control" name="shipping_attribute_2[__INDEX__]">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Attribute 3</label>
                        <input type="text" class="form-control" name="shipping_attribute_3[__INDEX__]">
                    </div>
                </div>
            </div>
        </div>
    </script>
    <script>
        (function ($) {
            var shippingIndex = <?php echo count($shippingFormData); ?>;

            function ensureDefaultSelection() {
                var $defaults = $('input[name="shipping_default"]:checked');
                if ($defaults.length === 0) {
                    $('input[name="shipping_default"]').first().prop('checked', true);
                }
            }

            function toggleRemoveButtons() {
                var $items = $('#shippingAddresses .shipping-address-item');
                if ($items.length === 1) {
                    $items.find('.remove-shipping-address').hide();
                } else {
                    $items.find('.remove-shipping-address').show();
                }
            }

            $('#addShippingAddress').on('click', function () {
                var template = $('#shippingAddressTemplate').html().replace(/__INDEX__/g, shippingIndex);
                $('#shippingAddresses').append(template);
                shippingIndex += 1;
                toggleRemoveButtons();
            });

            $('#shippingAddresses').on('click', '.remove-shipping-address', function () {
                var $items = $('#shippingAddresses .shipping-address-item');
                if ($items.length === 1) {
                    return;
                }
                $(this).closest('.shipping-address-item').remove();
                ensureDefaultSelection();
                toggleRemoveButtons();
            });

            toggleRemoveButtons();
            ensureDefaultSelection();

            if ($.fn.DataTable && $('#invoiceHistoryTable').length) {
                $('#invoiceHistoryTable').DataTable({
                    order: [[1, 'desc']],
                    pageLength: 10,
                    lengthChange: false,
                    searching: false
                });
            }
        })(jQuery);
    </script>
</body>
</html>



