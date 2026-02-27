# elgentos/magento2-hyva-checkout-ab-test

This extension allows you to set up an A/B test with different available Hyvä and the Luma fallback checkout.

You can configure a specific split between multiple checkouts, like the configured Hyva Checkout and the Luma fallback checkout. Or between the Hyva Checkout onepage version and the Hyva Checkout multi-step version.

## Installation

```bash
composer require elgentos/magento2-hyva-checkout-ab-test
bin/magento set:up
```

## Configuration

You can enable the extension under Stores > Configuration > Hyvä Themes > Checkout > A/B Test.

![image](https://user-images.githubusercontent.com/431360/201086503-6c54b1e0-68bd-4ec2-ab6b-e85bb52854b5.png)

## Usage

### Checkout Selection

The module determines which checkout to use based on the following priority:

1. **URL Parameter Override** (if allowed)
   - In **development mode**: The `active_checkout_namespace` URL parameter can always be used to manually select a checkout
   - In **production mode**: The parameter can only be used if "Allow URL Parameter Override" is enabled in configuration

2. **Developer Mode**: If no URL parameter is provided, development mode always uses the default checkout configured in `hyva_themes_checkout/general/checkout`

3. **Random Assignment**: In production mode (without URL override), customers are randomly assigned to configured checkouts based on their percentage split

### Manual Checkout Selection via URL

You can manually override the checkout selection by adding the `active_checkout_namespace` query parameter:

```
http://store.test/checkout?active_checkout_namespace=hyva
http://store.test/checkout?active_checkout_namespace=luma
```

**In Development Mode:** This parameter always works and overrides the default checkout selection.

**In Production Mode:** This parameter only works if "Allow URL Parameter Override" is enabled under Stores > Configuration > Hyvä Themes > Checkout > A/B Test.

## Reports

You can check the progress of the A/B test by running this query;

```sql
SET SQL_MODE='';
SELECT quote.active_checkout_namespace as checkout, COUNT(quote.entity_id) as quotes, COUNT(sales_order.quote_id) as orders, (COUNT(sales_order.quote_id) / COUNT(quote.entity_id)*100) as conversion_percentage
FROM quote
         LEFT JOIN sales_order ON quote.entity_id = sales_order.quote_id AND sales_order.state IN ('completed', 'processing')
WHERE quote.active_checkout_namespace IS NOT NULL
GROUP BY quote.active_checkout_namespace;
```

If you want to see the results in the Magento admin, you can install [degdigital/magento2-customreports](https://github.com/degdigital/magento2-customreports) and add the above query. Be sure to leave out the SQL_MODE part (Magento does this for you) and leave no trailing/leading white lines.

The report will look like this;

![image](https://user-images.githubusercontent.com/431360/200922747-6be0d031-3156-40cd-a989-c8399d4daae3.png)
