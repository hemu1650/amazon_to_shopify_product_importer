// PasswordContext.js
import React, { createContext, useState } from 'react';

const PasswordContext = createContext();

export const PasswordProvider = ({ children }) => {
  const [password, setPassword] = useState('');

  console.log("Password in context:", password);

  return (
    <PasswordContext.Provider value={{ password, setPassword }}>
      {children}
    </PasswordContext.Provider>
  );
};

export default PasswordContext;
