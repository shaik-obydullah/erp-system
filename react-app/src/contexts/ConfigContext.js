import { createContext, useContext, useState, useEffect, useCallback } from "react";
import { fetchConfiguration } from "../api/axios";

const ConfigContext = createContext();

export const useConfig = () => useContext(ConfigContext);

export const ConfigProvider = ({ children }) => {
  const [config, setConfig] = useState({
    currencySign: "$",
    vatPercentage: 0,
    taxPercentage: 0,
    dateFormat: "Y-m-d",
    timeFormat: "H:i:s",
    timezone: "UTC",
    projectName: "React POS",
  });

  const loadConfig = useCallback(async () => {
    // Configuration requires authentication; skip the call on the login screen.
    if (!localStorage.getItem("authToken")) return;

    try {
      const data = await fetchConfiguration();
      if (data) {
        setConfig({
          currencySign: data.currency_sign || "$",
          vatPercentage: parseFloat(data.vat_percentage) || 0,
          taxPercentage: parseFloat(data.tax_percentage) || 0,
          dateFormat: data.date_format || "Y-m-d",
          timeFormat: data.time_format || "H:i:s",
          timezone: data.timezone || "UTC",
          projectName: data.project_name || "React POS",
        });
      }
    } catch (error) {
      console.error("Failed to load configuration:", error);
    }
  }, []);

  useEffect(() => {
    loadConfig();
  }, [loadConfig]);

  return (
    <ConfigContext.Provider value={{ ...config, refreshConfig: loadConfig }}>
      {children}
    </ConfigContext.Provider>
  );
};

export default ConfigContext;
