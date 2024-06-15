import {
  Page,
  Layout,
  LegacyCard,
  Grid,
} from '@shopify/polaris';
import axios from 'axios';
import { useState, useEffect } from 'react';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

export default function Plans() {
  const [isLoading, setIsLoading] = useState(false);
  const [token, setToken] = useState('');
  const [key, setKey] = useState('1234');
const tempcode = localStorage.getItem('tempcode');
  const initialValues = {
    key: tempcode,
  };

  useEffect(() => {
    const getShopifyThemeId = async () => {
      try {
        const response = await axios.post(`${process.env.REACT_APP_BASE_URL}/authenticate`, initialValues);
        setToken(response.data.token);
      } catch (error) {
        console.error('Error fetching token:', error);
      }
    };
    getShopifyThemeId();
  }, []);

  const handleSubmit = async (planId) => {
    try {
      setIsLoading(true);
      const response = await axios.post(
        `https://shopify.infoshore.biz/aac/upgrade1.php?key=${key}&type=${planId}`,
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        }
      );
      console.log('Response:', response.data);
      setIsLoading(false);
      
      toast.success("Plan upgraded successfully!");
    } catch (error) {
      setIsLoading(false);
      if (error.response?.data?.error?.msg[0]) {
        toast.error(`Error: ${error.response.data.error.msg[0]}`);
      } else {
        toast.error("Something went wrong !!");
      }
      console.error('Error:', error);
    }
  };

  const titleStyle = {
    textAlign: 'center',
    border: '1px solid #dfe3e8',
    padding: '10px',
    borderRadius: '4px',
    marginBottom: '20px',
  };

  const activeButtonStyle = {
    backgroundColor: '#5cb85c',
    color: 'white',
    padding: '10px 20px',
    border: 'none',
    borderRadius: '4px',
    cursor: 'not-allowed',
    textDecoration: 'none',
    display: 'inline-block',
    marginTop: '10px',
  };

  const inactiveButtonStyle = {
    backgroundColor: '#f0ad4e',
    color: 'white',
    padding: '10px 20px',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    textDecoration: 'none',
    display: 'inline-block',
    marginTop: '10px',
  };

  const plans = [
    {
      title: "Basic Plan",
      price: "USD $4.95 /Month",
      features: [
        "✓ Unlimited product import",
        "✖ Manual Sync And Reimport",
        "✖ Import Amazon Reviews",
        "✖ Auto Sync Service With AWS API",
        "✖ Auto Sync Service Without AWS API",
        "✖ Bulk Import",
        "✖ Products CSV Export",
        "✖ Priority Customer Support",
      ],
      planId: 1,
    },
    {
      title: "Standard Plan",
      price: "USD $14.95 /Month",
      features: [
        "✓ Unlimited product import",
        "✓ Manual Sync And Reimport",
        "✖ Import Amazon Reviews",
        "✖ Auto Sync Service With AWS API",
        "✖ Auto Sync Service Without AWS API",
        "✖ Bulk Import",
        "✖ Products CSV Export",
        "✖ Priority Customer Support",
      ],
      planId: 2,
    },
    {
      title: "Premium Plan",
      price: "USD $29.95 /Month",
      features: [
        "✓ Unlimited product import",
        "✓ Manual Sync And Reimport",
        "✓ Import Amazon Reviews (Upto 1000 products)",
        "✓ Auto Sync Service (Upto 1000 products)",
        "✓ Bulk Import (Upto 1000 products)",
        "✓ Products CSV Export",
        "✓ Priority Customer Support",
      ],
      planId: 3,
    },
    {
      title: "Ultimate Plan",
      price: "USD $49.95 /Month",
      features: [
        "✓ Unlimited product import",
        "✓ Manual Sync And Reimport",
        "✓ Import Amazon Reviews (2500 products)",
        "✓ Auto Sync Service With AWS API (2500 products)",
        "✓ Auto Sync Service Without AWS API (2500 products)",
        "✓ Bulk Import (2500 products)",
        "✓ Products CSV Export",
        "✓ Priority Customer Support",
      ],
      planId: 4,
    },
  ];

  const profile = { plan: 2 };

  return (
    <Page fullWidth title='Plans'>
      <ToastContainer />
      <Layout>
        <Layout.Section variant="oneThird">
          <Grid>
            {plans.map((plan, index) => (
              <Grid.Cell
                key={index}
                columnSpan={{ xs: 3, sm: 3, md: 3, lg: 4, xl: 3 }}
                style={{
                  height: "100%",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                }}
              >
                <LegacyCard sectioned>
                  <div style={titleStyle}><strong>{plan.title}</strong></div>
                  <div className="panel-body" style={{ textAlign: 'center' }}>
                    <h4><strong>{plan.price}</strong></h4>
                    {plan.features.map((feature, idx) => (
                      <p key={idx}>{feature}</p>
                    ))}
                    {profile.plan === plan.planId ? (
                      <a href="javascript:void(0);" style={activeButtonStyle}>Activated</a>
                    ) : (
                      <a href="javascript:void(0);" style={inactiveButtonStyle} onClick={() => handleSubmit(plan.planId)}>Activate Now!</a>
                    )}
                  </div>
                </LegacyCard>
              </Grid.Cell>
            ))}
          </Grid>
        </Layout.Section>
      </Layout>
    </Page>
  );
}
