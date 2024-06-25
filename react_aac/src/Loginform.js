import React, { useState, useEffect, createContext, useContext } from 'react';
import { TextField, Button, FormLayout } from '@shopify/polaris';
import './Styles.css'; // Import custom CSS file
import axios from 'axios';
import { ToastContainer, toast } from 'react-toastify';
// import { useNavigate } from 'react-router-dom'; // Import useNavigate hook

// Step 1: Create a Context
const PasswordContext = createContext();

function LoginForm() {
    const [password, setPassword] = useState('');
    const [errors, setErrors] = useState({});
    const [isLoading, setIsLoading] = useState(false);
    const [token, setToken] = useState('');

    const handleSubmit = async (event, setPasswordValue) => {
        event.preventDefault();

        if (!password) {
            setErrors({ password: 'Password is required' });
            return;
        }

        const initialValues = {
            key: password,
        };

        try {
            setIsLoading(true);
            const response = await axios.post(`${process.env.REACT_APP_BASE_URL}/authenticate`, initialValues,{
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'Access-Control-Allow-Origin': '*',
                }
              }
            );       
            // Extract token from response data and set it in state
            const authToken = response.data.token;
            const tempcode = response.data.user.tempcode;
            const shopurl = response.data.user.shopurl;
            setToken(authToken);
            // Store token in local storage
            // localStorage.setItem('token', authToken);
            localStorage.setItem('tempcode', tempcode);
            localStorage.setItem('shopurl', shopurl);
            setIsLoading(false);
            toast.success("Submit successfully");
            window.location.reload(); // Refresh the page;
        } catch (error) {
            toast.error("Invalid detail");
            console.error('Error:', error);
        }
    };

    return (
        <FormLayout>
            <PasswordContext.Provider value={password}>
                <form className="login-form" onSubmit={(event) => handleSubmit(event, setPassword)}>
                    <h2>Amazon Associate Connector by InfoShoreApps</h2>
                    <p>Enter URL of your shopify store like myshop.myshopify.com</p>                
                    <div className="input-group">
                        <TextField
                            // label="Password"
                            value={password}
                            onChange={setPassword}
                            type="password"
                            autoComplete="current-password"
                            required
                            placeholder='Password'
                            error={errors.password ? errors.password : null}
                        />
                    </div>
                    <div className="submit-button">
                        <Button submit disabled={isLoading}>Login</Button>
                    </div>
                </form>
            </PasswordContext.Provider>
        </FormLayout>
    );
}

// Step 3: Create a custom hook to consume the context
export function usePassword() {
    return useContext(PasswordContext);
}

export default LoginForm;
