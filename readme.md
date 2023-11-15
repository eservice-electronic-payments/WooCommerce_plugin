[![GitHub license](https://img.shields.io/github/license/eservice-electronic-payments/WooCommerce_plugin)](https://github.com/eservice-electronic-payments/WooCommerce_plugin/blob/master/LICENSE)![Version](https://img.shields.io/badge/version-1.1.0-informational)

## eService Gateway for Woocommerce 
Requires at least: 4.1  
Tested up to: 4.9.5  
Stable tag: 1.4.0  
License: G  
License URI:    
Minimum WordPress Version: 5.2   
[Headless ready](#using-woocommerce-as-headless-ecommerce): Yes

## Description 

eService Gateway for Woocommerce is a Wordpress plugin which allows you to accept payments on your Woocommerce store.

## Installation 

This section describes how to install the plugin and get it working.

1. Login into the admin area of your website
2. Go to "Plugins" -> "Add New"
3. Click "Upload Plugin" link at the top of the page
4. Click "Browse" and navigate to the plugin's zip file and choose that file.
5. Click "Install Now" button
6. Wait while plugin is uploaded to your server
7. Add your merchant id and password on "Settings" page, and choose your payment solution
8. Click "Activate Plugin" button

For further instructions on how to install the plugin on WooCommerce please go to our Wiki [here](https://github.com/eservice-electronic-payments/woocommerce_beta/wiki/Installation-of-eService-Plugin-for-WooCommerce).

Got a question? Email wdrozenia_ecommerce@eservice.com.pl for help.

## Using WooCommerce as headless eCommerce

This section is for developers using WooCommerce as headless eCommerce, having separated frontend application communicating with WooCommerce via REST API/GraphQL.

Since version 1.3.0, an integration enables obtaining data to build a redirection form via REST API. It provides `POST` endpoint on the following URI: `https://your-wordpress-instance.local/wp-json/mmb-gateway/v1/redirect-data` where `https://your-wordpress-instance.local` is as base URL of your Wordpress instance. An endpoint expects JSON payload with a field called `order_key` containing an order key of WooCommerce order. Example TypeScript client:

```ts
type EServiceEndpointPayload = {
  order_key: string;
};

type EServiceEndpointResponse = {
  token: string,
  merchantId: string,
  integrationMode: "hostedPayPage" | "standalone",
  cashierUrl: string,
  automaticForm: boolean
};

type EServiceEndpointError = {
  code: string,
  message: string,
  data: {
    status: number,
    [key: string]: any
  },
}

const baseURL = "http://localhost:8888";
const getEServiceRedirectData = async (payload: EServiceEndpointPayload): Promise<EServiceEndpointResponse> => {
  const endpointUrl = new URL("wp-json/mmb-gateway/v1/redirect-data", baseURL);
  const response = await fetch(endpointUrl, {
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    method: 'POST',
    body: JSON.stringify(payload),
  });

  if (!response.ok || response.status === 204) {
    const error = await response.json() as EServiceEndpointError;
    throw new Error(error.message);
  }
  return await response.json() as EServiceEndpointResponse;
};
```

Example usage:

```ts
// Inside async function
try {
  const responseBody = await getEServiceRedirectData({
    order_key: 'wc_order_xyz123'
  });
  responseBody; // Here I have everything!
} catch (err: unknown) {
  console.error((err as Error).message);
}
```

### Possible errors

#### No payload found

Occurs if the developer didn't attach request's body.

```json
{
  "code": "no_payload",
  "message": "No payload found",
  "data": {
    "status": 400
  }
}
```

#### No order key provided

Occurs if the developer didn't attach `order_key` inside request's body.

```json
{
  "code": "no_order_key",
  "message": "No order key provided",
  "data": {
    "status": 400
  }
}
```

#### No order found for provided key

Occurs if an order for provided `order_key` doesn't exist.

```json
{
  "code": "no_order",
  "message": "No order found for provided key",
  "data": {
    "status": 404
  }
}
```

#### Invalid order

Occurs if found `order_id` for provided `order_key` but for some reason couldn't find an order with this ID. It means that something might be broken in order's structure or that order is broken.

```json
{
  "code": "invalid_order",
  "message": "Order not found",
  "data": {
    "status": 500
  }
}
```

#### Already paid

Occurs if an order is already paid.

```json
{
  "code": "already_paid",
  "message": "Already paid",
  "data": {
    "status": 204
  }
}
```
