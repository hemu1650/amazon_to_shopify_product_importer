import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

const Logout = ({ setToken }) => {
  const navigate = useNavigate();

  useEffect(() => {
    setToken(null);  // Clear the token
    navigate('/');   // Redirect to the login page
  }, [setToken, navigate]);

  return null;  // This component doesn't render anything
};

export default Logout;
